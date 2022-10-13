<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Joomla\Plugin\Task\AdminTools\Extension\SubTask;

defined('_JEXEC') or die;

use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status;
use Joomla\Component\Scheduler\Administrator\Task\Task;

/**
 * Scheduled Task type to remove old Blocked Request Log entries
 *
 * @since 7.1.2
 */
trait RemoveOldLogEntries
{
	/**
	 * How many old entries to delete every time this feature executes.
	 *
	 * @since 7.1.2
	 */
	private static $DELETE_BATCH_SIZE = 10000;

	/**
	 * Removes old Blocked Requests Log entries beyond a certain number to keep.
	 *
	 * @param   ExecuteTaskEvent  $event
	 *
	 * @return  int
	 * @since   7.1.2
	 */
	private function removeOldLogEntries(ExecuteTaskEvent $event): int
	{
		// Get some basic information about the task at hand.
		/** @var Task $task */
		$task          = $event->getArgument('subject');
		$params        = $event->getArgument('params') ?: (new \stdClass());
		$entriesToKeep = $params->maxlogentries ?? 0;

		if ($entriesToKeep <= 0)
		{
			return Status::OK;
		}

		/**
		 * Delete up to 10,000 old entries
		 *
		 * IMPORTANT! We need a subquery-inside-a-subquery to avoid a MySQL limitation which does not allow IN arguments
		 * to be subqueries with LIMITs.
		 *
		 * The SQL to run is:
		 * DELETE FROM `#__admintools_log` WHERE `id` IN (SELECT * FROM (SELECT `id` FROM `#__admintools_log` ORDER BY
		 * `id` desc LIMIT $entriesToKeep, self::DELETE_BATCH_SIZE) `foo`)
		 */
		$db = $this->db;

		$innerSubquery = $db->getQuery(true)
			->select($db->quoteName('id'))
			->from($db->quoteName('#__admintools_log'))
			->order($db->quoteName('id') . ' DESC')
			->setLimit(self::$DELETE_BATCH_SIZE, $entriesToKeep);

		$outerSubquery = $db->getQuery(true)
			->select('*')
			->from('(' . $innerSubquery . ') ' . $db->quoteName('foo'));

		$query = $db->getQuery(true)
			->delete($db->quoteName('#__admintools_log'))
			->where($db->quoteName('id') . ' IN (' . $outerSubquery . ')');

		try
		{
			$db->setQuery($query)->execute();
			$affectedRows = $db->getAffectedRows();
		}
		catch (\Exception $exc)
		{
			// Do nothing on DB exception
			$affectedRows = 0;
		}

		return ($affectedRows === self::$DELETE_BATCH_SIZE) ? Status::WILL_RESUME : Status::OK;
	}
}