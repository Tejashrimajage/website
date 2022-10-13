<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Joomla\Plugin\Task\AdminTools\Extension\SubTask;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status;
use Joomla\Component\Scheduler\Administrator\Task\Task;
use Joomla\Registry\Registry;

/**
 * Delete inactive Joomla user records
 *
 * @since  7.1.2
 */
trait DeleteInactiveUsers
{
	/**
	 * Delete inactive Joomla user records
	 *
	 * @param   ExecuteTaskEvent  $event  The scheduled task event we are handling
	 *
	 * @return  int
	 * @since   7.1.2
	 */
	private function deleteInactiveUsers(ExecuteTaskEvent $event): int
	{
		// Get some basic information about the task at hand.
		/** @var Task $task */
		$task       = $event->getArgument('subject');
		$params     = $event->getArgument('params') ?: (new Registry());
		$filtertype = (int) $params->deleteinactive ?? 1;
		$days       = (int) $params->deleteinactive_days ?? 0;


		$schedulerParams = ComponentHelper::getParams('com_scheduler');
		$timeLimit       = intdiv(intval($schedulerParams->get('timeout', 300) ?: 300), 2);
		$phpTimeLimit    = function_exists('ini_get') ? ini_get('max_execution_time') : 0;

		$timeLimit = ($phpTimeLimit > 0) ? min($timeLimit, $phpTimeLimit) : $timeLimit;

		$hasMore         = true;
		$totalElapsed    = 0.0;
		$lastStepElapsed = 0.0;
		$maxStepElapsed  = 0.0;

		while (true)
		{
			$totalElapsed   += $lastStepElapsed;
			$maxStepElapsed = max($maxStepElapsed, $lastStepElapsed);

			if ($totalElapsed >= ($timeLimit - $maxStepElapsed))
			{
				// We have hit or are about to hit a timeout
				break;
			}

			$stepStart = microtime(true);
			$db        = $this->db;
			$nullDate  = $db->getNullDate();
			$sql       = $db->getQuery(true)
			                ->select($db->qn('id'))
			                ->from($db->qn('#__users'))
			                ->where($db->qn('registerDate') . ' <= ' . "DATE_SUB(NOW(), INTERVAL $days DAY)")
			                ->extendWhere('AND', [
				                $db->quoteName('lastvisitDate') . ' = :nullDate',
				                $db->quoteName('lastvisitDate') . ' IS NULL',
			                ], 'OR')
			                ->bind(':nullDate', $nullDate);

			switch ($filtertype)
			{
				case 1:
					// Only users not yet activated
					$sql->extendWhere('AND NOT', [
						$db->quoteName('activation') . ' = ' . $db->quote(''),
						$db->quoteName('activation') . ' IS NULL',
					], 'OR');
					break;

				case 2:
					// Only users already activated
					$sql
						->extendWhere('AND', [
							$db->quoteName('activation') . ' = ' . $db->quote(''),
							$db->quoteName('activation') . ' IS NULL',
						], 'OR')
						->extendWhere('AND', [
							$db->quoteName('lastResetTime') . ' IS NULL',
							$db->quoteName('lastResetTime') . ' = :nullDate20',
						], 'OR')
						->bind(':nullDate20', $nullDate);
					break;

				case 3:
					// All users who haven't logged in
					break;
			}


			$db->setQuery($sql, 0, 1);

			$id = $db->loadResult();

			if (empty($id))
			{
				// No more users to process.
				$hasMore = false;

				break;
			}

			$userToKill = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($id);
			$userToKill->delete();

			$lastStepElapsed = microtime(true) - $stepStart;
		}

		return $hasMore ? Status::WILL_RESUME : Status::OK;
	}

}