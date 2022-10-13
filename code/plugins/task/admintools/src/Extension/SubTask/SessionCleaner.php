<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Joomla\Plugin\Task\AdminTools\Extension\SubTask;

defined('_JEXEC') or die;

use Akeeba\Component\AdminTools\Administrator\Model\DatabasetoolsModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory as JoomlaFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryServiceInterface;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status;
use Joomla\Component\Scheduler\Administrator\Task\Task;

/**
 * Clean up the session metadata
 *
 * @since  7.1.2
 */
trait SessionCleaner
{
	/**
	 * Clean up the session metadata
	 *
	 * @param   ExecuteTaskEvent  $event  The scheduled task event we are handling
	 *
	 * @return  int
	 * @since   7.1.2
	 */
	private function sessionCleaner(ExecuteTaskEvent $event): int
	{
		// Get some basic information about the task at hand.
		/** @var Task $task */
		$task   = $event->getArgument('subject');
		$params = $event->getArgument('params') ?: (new \stdClass());

		if (!ComponentHelper::isEnabled('com_admintools'))
		{
			return Status::OK;
		}

		// Make sure Admin Tools is installed and enabled.
		$component = ComponentHelper::isEnabled('com_admintools')
			? JoomlaFactory::getApplication()->bootComponent('com_admintools')
			: null;

		if (!($component instanceof MVCFactoryServiceInterface))
		{
			throw new \RuntimeException('The Admin Tools component is not installed or has been disabled.');
		}

		$factory = $component->getMVCFactory();

		/** @var DatabasetoolsModel $model */
		$model = $factory->createModel('Databasetools', 'Administrator', ['ignore_request' => true]);

		$model->garbageCollectSessions();

		return Status::OK;
	}
}