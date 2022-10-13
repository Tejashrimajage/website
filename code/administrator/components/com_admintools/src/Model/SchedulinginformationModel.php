<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;

class SchedulinginformationModel extends BaseModel
{
	public function getPaths()
	{
		$ret = (object) [
			'cli'      => (object) [
				'supported' => false,
				'path'      => false,
			],
			'joomla'      => (object) [
				'supported' => false,
			],
			'frontend' => (object) [
				'supported' => false,
				'path'      => false,
			],
			'info'     => (object) [
				'windows'   => false,
				'php_path'  => false,
				'root_url'  => false,
				'secret'    => '',
				'feenabled' => false,
			],
		];

		// Get the absolute path to the site's root
		$absolute_root = rtrim(realpath(JPATH_ROOT), '/\\');
		// Is this Windows?
		$ret->info->windows = (DIRECTORY_SEPARATOR == '\\') || (substr(strtoupper(PHP_OS), 0, 3) == 'WIN');
		// Get the pseudo-path to PHP CLI
		if ($ret->info->windows)
		{
			$ret->info->php_path = 'c:\path\to\php.exe';
		}
		else
		{
			$ret->info->php_path = '/path/to/php';
		}
		// Get front-end backup secret key
		$cParams              = ComponentHelper::getParams('com_admintools');
		$ret->info->secret    = $cParams->get('frontend_secret_word', '');
		$ret->info->feenabled = $cParams->get('frontend_enable', false);
		// Get root URL
		$ret->info->root_url = rtrim(Uri::root(), '/');

		// Get information for CLI CRON script
		$ret->cli->supported = true;
		$ret->cli->path      = $absolute_root . DIRECTORY_SEPARATOR . 'cli' . DIRECTORY_SEPARATOR . 'joomla.php';

		// Get information for Joomla Scheduled Tasks
		$ret->joomla->supported = version_compare(JVERSION, '4.1.0', 'ge') &&
			PluginHelper::isEnabled('task', 'admintools');

		// Get information for front-end backup
		$ret->frontend->supported = true;
		if (trim($ret->info->secret) && $ret->info->feenabled)
		{
			$ret->frontend->path = 'index.php?option=com_admintools&view=filescanner&format=raw&key='
				. urlencode($ret->info->secret);
		}

		return $ret;
	}

}