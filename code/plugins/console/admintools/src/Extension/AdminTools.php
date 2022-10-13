<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Joomla\Plugin\Console\AdminTools\Extension;

defined('_JEXEC') or die;

use Joomla\Application\ApplicationEvents;
use Joomla\Application\Event\ApplicationEvent;
use Joomla\CMS\Application\ConsoleApplication;
use Joomla\CMS\Factory as JoomlaFactory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Joomla\Filesystem\Folder;
use Joomla\Plugin\Console\AdminTools\Command\CommandFactoryInterface;
use Throwable;

class AdminTools extends CMSPlugin implements SubscriberInterface
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  7.0.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * Application object.
	 *
	 * @var    ConsoleApplication
	 * @since  7.0.0
	 */
	protected $app;

	/**
	 * Admin Tools CLI Command Factory object instance.
	 *
	 * @var   CommandFactoryInterface
	 * @since 7.0.0
	 */
	protected $commandFactory;

	public function __construct(&$subject, CommandFactoryInterface $factory, $config = [])
	{
		parent::__construct($subject, $config);

		$this->commandFactory = $factory;
	}


	/**
	 * Returns an array of events this subscriber will listen to.
	 *
	 * @return  array
	 *
	 * @since   7.0.0
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			ApplicationEvents::BEFORE_EXECUTE => 'registerCLICommands',
		];
	}

	/**
	 * Registers command classes to the CLI application.
	 *
	 * This is an event handled for the ApplicationEvents::BEFORE_EXECUTE event.
	 *
	 * @param   ApplicationEvent  $event  The before_execite application event being handled
	 *
	 * @since        7.0.0
	 *
	 * @noinspection PhpUnused
	 */
	public function registerCLICommands(ApplicationEvent $event)
	{
		/** @var ConsoleApplication $app */
		$app = $event->getApplication();

		// Only register CLI commands if we can boot up the Akeeba Backup component enough to make it usable.
		try
		{
			$this->initialiseComponent($this->app);
		}
		catch (Throwable $e)
		{
			return;
		}

		// Try to find all commands in the CliCommands directory of the component
		$files         = Folder::files(JPATH_ADMINISTRATOR . '/components/com_admintools/src/CliCommand', '.php');
		$files         = is_array($files) ? $files : [];

		foreach ($files as $file)
		{
			/**
			 * Try to instantiate and register each command object, going through the Admin Tools CLI command factory.
			 *
			 * The try/catch block has a rationale behind it. We get the command name by removing the .php extension
			 * from the base name of the file. This is combined with the root namespace of CLI commands to construct the
			 * class FQN we will be trying to instantiate.
			 *
			 * However, some hosts create copies of the files e.g. copying or renaming FooBar.php to FooBar.01.php or
			 * even FooBar_01.php. This is something we've seen and documented since 2013, mostly attributed to some
			 * hosts' really broken file scanners. These files would create invalid class names. Since these class names
			 * do not exist, trying to instantiate them will fail with a RuntimeException from the factory. We catch
			 * this and move on, ignoring the bad file.
			 *
			 * Further to that, it's possible that a different combination of unforeseen mistakes by the host and / or
			 * Joomla (e.g. not all files copied correctly on update) causing one or more CLI command classes to error
			 * out. This is why we are catching Throwable instead of just RuntimeException.
			 */
			try
			{
				$app->addCommand(
					$this->commandFactory->getCLICommand(basename($file, '.php'))
				);
			}
			catch (Throwable $e)
			{
			}
		}
	}

	private function initialiseComponent(ConsoleApplication $app): void
	{
		// Load the Admin Tools language files
		$lang = JoomlaFactory::getApplication()->getLanguage();
		$lang->load('com_admintools', JPATH_SITE, 'en-GB', true, true);
		$lang->load('com_admintools', JPATH_SITE, null, true, false);
		$lang->load('com_admintools', JPATH_ADMINISTRATOR, 'en-GB', true, true);
		$lang->load('com_admintools', JPATH_ADMINISTRATOR, null, true, false);

		// Make sure we have a version loaded
		@include_once(JPATH_ADMINISTRATOR . '/components/com_admintools/version.php');

		if (!defined('ADMINTOOLS_VERSION'))
		{
			define('ADMINTOOLS_VERSION', 'dev');
			define('ADMINTOOLS_DATE', date('Y-m-d'));
		}
	}
}