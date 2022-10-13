<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Joomla\Plugin\Task\AdminTools\Extension;

defined('_JEXEC') or die;

use Exception;
use InvalidArgumentException;
use Joomla\Application\ApplicationInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Database\DatabaseDriver;
use Joomla\Event\EventInterface;
use Joomla\Event\SubscriberInterface;
use Joomla\Plugin\Task\AdminTools\Extension\SubTask\AutoImport;
use Joomla\Plugin\Task\AdminTools\Extension\SubTask\CacheCleaner;
use Joomla\Plugin\Task\AdminTools\Extension\SubTask\DeleteInactiveUsers;
use Joomla\Plugin\Task\AdminTools\Extension\SubTask\PhpFileChangeScanner;
use Joomla\Plugin\Task\AdminTools\Extension\SubTask\RemoveOldLogEntries;
use Joomla\Plugin\Task\AdminTools\Extension\SubTask\SessionCleaner;
use Joomla\Plugin\Task\AdminTools\Extension\SubTask\SessionOptimizer;
use Joomla\Plugin\Task\AdminTools\Extension\SubTask\TaskRegistryAware;
use Joomla\Plugin\Task\AdminTools\Extension\SubTask\TempDirCleaner;

/**
 * Integration with Joomla Scheduled Tasks
 *
 * @since 7.1.2
 */
class AdminTools extends CMSPlugin implements SubscriberInterface
{
	use TaskPluginTrait;
	use TaskRegistryAware;
	use PhpFileChangeScanner;
	use RemoveOldLogEntries;
	use SessionOptimizer;
	use SessionCleaner;
	use CacheCleaner;
	use TempDirCleaner;
	use DeleteInactiveUsers;
	use AutoImport;

	private const TASKS_MAP = [
		'admintools.scan'                => [
			'langConstPrefix' => 'PLG_TASK_ADMINTOOLS_TASK_SCAN',
			'method'          => 'scan',
			'form'            => 'scanForm',
		],
		'admintools.removeOldLogEntries' => [
			'langConstPrefix' => 'PLG_TASK_ADMINTOOLS_TASK_REMOVEOLDLOGENTRIES',
			'method'          => 'removeOldLogEntries',
			'form'            => 'removeOldLogEntriesForm',
		],
		'admintools.SessionOptimizer'    => [
			'langConstPrefix' => 'PLG_TASK_ADMINTOOLS_TASK_SESSIONOPTIMIZER',
			'method'          => 'sessionOptimizer',
		],
		'admintools.sessionCleaner'      => [
			'langConstPrefix' => 'PLG_TASK_ADMINTOOLS_TASK_SESSIONCLEANER',
			'method'          => 'sessionCleaner',
		],
		'admintools.cacheCleaner'        => [
			'langConstPrefix' => 'PLG_TASK_ADMINTOOLS_TASK_CACHECLEANER',
			'method'          => 'cacheCleaner',
			'form'            => 'cacheCleanerForm',
		],
		'admintools.tempDirCleaner'      => [
			'langConstPrefix' => 'PLG_TASK_ADMINTOOLS_TASK_TEMPDIRCLEANER',
			'method'          => 'tempDirCleaner',
		],
		'admintools.deleteInactiveUsers' => [
			'langConstPrefix' => 'PLG_TASK_ADMINTOOLS_TASK_DELETEINACTIVEUSERS',
			'method'          => 'deleteInactiveUsers',
			'form'            => 'deleteInactiveUsersForm',
		],
		'admintools.autoImport'          => [
			'langConstPrefix' => 'PLG_TASK_ADMINTOOLS_TASK_AUTOIMPORT',
			'method'          => 'autoImport',
			'form'            => 'autoImportForm',
		],
	];

	/**
	 * The application under which we are running.
	 *
	 * @var   ApplicationInterface
	 * @since 7.1.2
	 */
	protected $app;

	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var   boolean
	 * @since 7.1.2
	 */
	protected $autoloadLanguage = true;

	/**
	 * The application's database driver object
	 *
	 * @var   DatabaseDriver
	 * @since 7.1.2
	 */
	protected $db;

	/**
	 * Returns an array of events this subscriber will listen to.
	 *
	 * This is mostly boilerplate code as per every built-in Task plugin in Joomla.
	 *
	 * @return  array
	 * @since   7.1.2
	 */
	public static function getSubscribedEvents(): array
	{
		// This task is disabled if the Akeeba Backup component is not installed or has been unpublished
		if (!ComponentHelper::isEnabled('com_admintools'))
		{
			return [];
		}

		return [
			'onTaskOptionsList'    => 'advertiseRoutines',
			'onExecuteTask'        => 'standardRoutineHandler',
			'onContentPrepareForm' => 'enhanceTaskItemForm',
		];
	}

	/**
	 * Enhance the task form with routine-specific fields from an XML file declared through the TASKS_MAP constant.
	 * If a plugin only supports the task form and does not need additional logic, this method can be mapped to the
	 * `onContentPrepareForm` event through {@see SubscriberInterface::getSubscribedEvents()} and will take care
	 * of injecting the fields without additional logic in the plugin class.
	 *
	 * @param   EventInterface|Form  $context  The onContentPrepareForm event or the Form object.
	 * @param   mixed                $data     The form data, required when $context is a {@see Form} instance.
	 *
	 * @return  boolean  True if the form was successfully enhanced or the context was not relevant.
	 *
	 * @throws  Exception
	 * @since   7.1.2
	 */
	public function enhanceTaskItemForm($context, $data = null): bool
	{
		if ($context instanceof EventInterface)
		{
			/** @var Form $form */
			$form = $context->getArgument('0');
			$data = $context->getArgument('1');
		}
		elseif ($context instanceof Form)
		{
			$form = $context;
		}
		else
		{
			throw new InvalidArgumentException(
				sprintf(
					'Argument 0 of %1$s must be an instance of %2$s or %3$s',
					__METHOD__,
					EventInterface::class,
					Form::class
				)
			);
		}

		if ($form->getName() !== 'com_scheduler.task')
		{
			return true;
		}

		$routineId           = $this->getRoutineId($form, $data);
		$isSupported         = array_key_exists($routineId, self::TASKS_MAP);
		$enhancementFormName = self::TASKS_MAP[$routineId]['form'] ?? '';

		// Return if routine is not supported by the plugin or the routine does not have a form linked in TASKS_MAP.
		if (!$isSupported || strlen($enhancementFormName) === 0)
		{
			return true;
		}

		// We expect the form XML in "{PLUGIN_PATH}/forms/{FORM_NAME}.xml"
		$path                = JPATH_PLUGINS . '/' . $this->_type . '/' . $this->_name;
		$enhancementFormFile = $path . '/forms/' . $enhancementFormName . '.xml';

		try
		{
			$enhancementFormFile = Path::check($enhancementFormFile);
		}
		catch (Exception $e)
		{
			return false;
		}

		if (is_file($enhancementFormFile))
		{
			return $form->loadFile($enhancementFormFile);
		}

		return false;
	}

}