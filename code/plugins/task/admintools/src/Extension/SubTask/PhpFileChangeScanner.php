<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Joomla\Plugin\Task\AdminTools\Extension\SubTask;

defined('_JEXEC') or die;

use Akeeba\Component\AdminTools\Administrator\Model\ScansModel;
use Akeeba\Component\AdminTools\Administrator\Scanner\Util\Configuration;
use Akeeba\Component\AdminTools\Administrator\Scanner\Util\Session;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory as JoomlaFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryServiceInterface;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status;
use Joomla\Component\Scheduler\Administrator\Task\Task;
use Joomla\Registry\Registry;

trait PhpFileChangeScanner
{
	/**
	 * Scan the site with Admin Tools' PHP File Change Scanner.
	 *
	 * This is a resumable task.
	 *
	 * @param   ExecuteTaskEvent  $event
	 *
	 * @return  int
	 */
	private function scan(ExecuteTaskEvent $event): int
	{
		// Get some basic information about the task at hand.
		/** @var Task $task */
		$task         = $event->getArgument('subject');
		$params       = $event->getArgument('params') ?: (new \stdClass());
		$timeLimitCli = (int) $params->time_limit_cli ?? -1;
		$timeLimitWeb = (int) $params->time_limit_web ?? -1;

		// Make sure Admin Tools is installed and enabled.
		$component = ComponentHelper::isEnabled('com_admintools')
			? JoomlaFactory::getApplication()->bootComponent('com_admintools')
			: null;

		if (!($component instanceof MVCFactoryServiceInterface))
		{
			throw new \RuntimeException('The Admin Tools component is not installed or has been disabled.');
		}

		// Set up the Scan model
		$factory = $component->getMVCFactory();
		/** @var ScansModel $model */
		$model = $factory->createModel('Scans', 'Administrator');

		/**
		 * Modify the file scanner configuration to run up to 50% of the available task time or 100 seconds,
		 * whichever is lower, but not less than 3 seconds per step (throws an error if there is not enough time).
		 */
		$configuration    = Configuration::getInstance();
		$schedulerParams  = ComponentHelper::getParams('com_scheduler');
		$schedulerTimeout = $schedulerParams->get('timeout', 300) ?: 300;
		$phpTimeLimit     = function_exists('ini_get') ? ini_get('max_execution_time') : 0;

		if (method_exists($this->app, 'isClient') && $this->app->isClient('cli'))
		{
			if ($timeLimitCli <= 0)
			{
				$timeLimitCli = intdiv($schedulerTimeout, 2);
			}

			$maxTime = $timeLimitCli;
		}
		else
		{
			// We are running inside a web application.
			if (($timeLimitWeb ?: 0) <= 0)
			{
				$maxExec = $configuration->get('maxExec') ?: 5;

				$timeLimitWeb = min($maxExec, intdiv($schedulerTimeout, 2));
			}

			$maxTime = $timeLimitWeb;
		}

		if ($phpTimeLimit > 0)
		{
			$maxTime = min($phpTimeLimit, $maxTime);
		}

		$configuration->set('maxExec', $maxTime);
		$configuration->set('minExec', 0);
		$configuration->set('runtimeBias', 90);

		// Get the scan session utility class to find the information we have to persist between task executions.
		$scanSession = new Session();

		// Am I resuming a scan or starting a new one?
		if ($task->get('last_exit_code', Status::OK) == Status::WILL_RESUME)
		{
			$this->logTask(sprintf('Resuming task %d', $task->get('id')));
			$this->loadTaskRegistry($event);

			// Populate the scan session from the saved task information
			foreach ($scanSession->getKnownKeys() as $key)
			{
				$value = $this->taskInfoRegistry->get($key, null);

				if ($value !== null)
				{
					$scanSession->set($key, $value);
				}
			}

			// Run another step of the scanner
			$ret = $model->stepScan();
		}
		else
		{
			$this->taskInfoRegistry = new Registry();

			$this->logTask(sprintf('Starting new task %d', $task->get('id')));

			// Remove previous, incomplete scans
			$model->removeIncompleteScans();

			// Start scanning
			$ret = $model->startScan('joomla');
		}

		// Save the scan engine state
		foreach ($scanSession->getKnownKeys() as $key)
		{
			$value = $scanSession->get($key);

			$this->taskInfoRegistry->set($key, $value);
		}

		// Remove the scan engine state from the user session
		$scanSession->reset();

		// Did the scan end in an error?
		if (isset($ret['error']) && !empty($ret['error']))
		{
			$this->removeTaskRegistry($event);

			throw new \RuntimeException($ret['Error']);
		}

		foreach ($ret['warnings'] as $warning)
		{
			$this->logTask($warning, 'warning');
		}


		// Should I resume the scan?
		$willResume = !($ret['done'] ?: false);

		if ($willResume)
		{
			$this->logTask('The scan will resume next time this scheduled task is told to run.');

			$this->saveTaskRegistry($event);
		}
		else
		{
			$this->logTask('The scan completed successfully.');

			$this->removeTaskRegistry($event);
		}

		return $willResume ? Status::WILL_RESUME : Status::OK;
	}
}