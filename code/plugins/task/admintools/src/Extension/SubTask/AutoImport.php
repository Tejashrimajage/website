<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Joomla\Plugin\Task\AdminTools\Extension\SubTask;

defined('_JEXEC') or die;

use Akeeba\Component\AdminTools\Administrator\Model\ExportimportModel;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status;
use Joomla\Component\Scheduler\Administrator\Task\Task;

/**
 * Automatically import Admin Tools configuration settings from a URL
 *
 * @since      7.1.2
 */
trait AutoImport
{
	/**
	 * Automatically import Admin Tools configuration settings from a URL
	 *
	 * @param   ExecuteTaskEvent  $event  The scheduled task event we are handling
	 *
	 * @return  int
	 * @since   7.1.2
	 */
	private function autoImport(ExecuteTaskEvent $event): int
	{
		// Get some basic information about the task at hand.
		/** @var Task $task */
		$task   = $event->getArgument('subject');
		$params = $event->getArgument('params') ?: (new \stdClass());
		$url    = $params->autoimport_url ?? '';

		if (empty($url))
		{
			return Status::OK;
		}

		$http = HttpFactory::getHttp();

		$response = $http->get($url);
		$settings = $response->getBody();

		if ($response->getStatusCode() > 299)
		{
			throw new \RuntimeException(sprintf("Cannot download Admin Tools settings from %s (HTTP status %u)", $url, $response->getStatusCode()));
		}

		if (empty($settings))
		{
			throw new \RuntimeException(sprintf("Cannot download Admin Tools settings from %s (no content in the remote server response)", $url));
		}

		/** @var MVCFactoryInterface $factory */
		$factory = $this->app->bootComponent('com_admintools')->getMVCFactory();
		/** @var ExportimportModel $importModel */
		$importModel = $factory->createModel('Exportimport', 'Administrator', ['ignore_request' => true]);

		$importModel->importData($settings);

		return Status::OK;
	}
}