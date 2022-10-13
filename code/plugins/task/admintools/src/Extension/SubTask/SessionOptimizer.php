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
use Joomla\Database\DatabaseQuery;
use Joomla\Database\Exception\ConnectionFailureException;
use Joomla\Database\Exception\ExecutionFailureException;
use Joomla\Database\Pdo\PdoDriver;
use Joomla\Database\QueryInterface;

/**
 * Scheduled Task type to repair and optimise the Joomla #__session table
 *
 * @since 7.1.2
 */
trait SessionOptimizer
{
	/**
	 * Executes an unprepared SQL statement.
	 *
	 * The PDO driver doesn't distinguish between prepared and unprepared statements. Therefore we can just run anything
	 * we please. The MySQLi driver, however, has a distinction between prepared and unprepared statements. We cannot
	 * run certain SQL comments (such as OPTIMIZE and REPAIR) over a prepared statement. The MySQLi driver has a handy
	 * method called executeUnpreparedStatement which is protected and which runs this kind of statements.
	 *
	 * This here method tries to figure out if the database driver object has that method and use it instead of the
	 * prepared statement.
	 *
	 * @param   DatabaseQuery|QueryInterface  $sql
	 *
	 * @return  bool|mixed
	 * @since   7.1.2
	 */
	private function executeUnpreparedGetObjectList($sql)
	{
		$sql        = $this->db->replacePrefix($sql);
		$connection = $this->db->getConnection();

		if (($this->db instanceof PdoDriver) || !($connection instanceof \mysqli))
		{
			return $this->db->setQuery($sql)->loadObjectList();
		}

		$cursor = $connection->query($sql);

		// If an error occurred handle it.
		if (!$cursor)
		{
			$errorNum = (int) $connection->errno;
			$errorMsg = (string) $connection->error;

			// Check if the server was disconnected.
			if (!$this->db->connected())
			{
				try
				{
					// Attempt to reconnect.
					$connection = null;
					$this->db->connect();
				}
				catch (ConnectionFailureException $e)
				{
					throw new ExecutionFailureException($sql, $errorMsg, $errorNum);
				}

				// Since we were able to reconnect, run the query again.
				return $this->executeUnpreparedGetObjectList($sql);
			}

			// The server was not disconnected.
			throw new ExecutionFailureException($sql, $errorMsg, $errorNum);
		}

		$result = [];

		while ($row = mysqli_fetch_object($cursor))
		{
			$result[] = $row;
		}

		$cursor->free_result();

		return $result;
	}

	/**
	 * Executes an unprepared SQL statement.
	 *
	 * The PDO driver doesn't distinguish between prepared and unprepared statements. Therefore we can just run anything
	 * we please. The MySQLi driver, however, has a distinction between prepared and unprepared statements. We cannot
	 * run certain SQL comments (such as OPTIMIZE and REPAIR) over a prepared statement. The MySQLi driver has a handy
	 * method called executeUnpreparedStatement which is protected and which runs this kind of statements.
	 *
	 * This here method tries to figure out if the database driver object has that method and use it instead of the
	 * prepared statement.
	 *
	 * @param   string|QueryInterface  $sql
	 *
	 * @return  bool|mixed
	 * @since   7.1.2
	 */
	private function executeUnpreparedQuery($sql)
	{
		$sql    = $this->db->replacePrefix($sql);
		$db     = $this->db;
		$refObj = new \ReflectionObject($db);

		try
		{
			$method = $refObj->getMethod('executeUnpreparedQuery');
			$method->setAccessible(true);

			return $method->invoke($db, $sql);
		}
		catch (\ReflectionException $e)
		{
			return $db->setQuery($sql)->execute();
		}
	}

	/**
	 * Repairs and optimises Joomla's #__session table
	 *
	 * @param   ExecuteTaskEvent  $event
	 *
	 * @return  int
	 * @since   7.1.2
	 */
	private function sessionOptimizer(ExecuteTaskEvent $event): int
	{
		// Get some basic information about the task at hand.
		/** @var Task $task */
		$task = $event->getArgument('subject');

		$db     = $this->db;
		$dbName = $db->getName();

		// Make sure this is MySQL
		if (!in_array(strtolower($dbName), ['mysql', 'mysqli', 'pdomysql']))
		{
			return Status::OK;
		}

		$result = $this->executeUnpreparedGetObjectList('CHECK TABLE ' . $db->quoteName('#__session'));

		$isOK = false;

		if (!empty($result))
		{
			foreach ($result as $row)
			{
				if (($row->Msg_type == 'status') && (
						($row->Msg_text == 'OK') ||
						($row->Msg_text == 'Table is already up to date')
					)
				)
				{
					$isOK = true;
				}
			}
		}

		// Run a repair only if it is required
		if (!$isOK)
		{
			$this->executeUnpreparedQuery('REPAIR TABLE ' . $db->quoteName('#__session'));
		}

		// Finally, optimize
		$this->executeUnpreparedQuery('OPTIMIZE TABLE ' . $db->quoteName('#__session'));

		return Status::OK;
	}
}