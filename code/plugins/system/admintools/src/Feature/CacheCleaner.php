<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Joomla\Plugin\System\AdminTools\Feature;

defined('_JEXEC') || die;

use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;

/**
 * @deprecated 8.0  Use the Joomla Scheduled Tasks instead
 */
class CacheCleaner extends Base
{
	/**
	 * Is this feature enabled?
	 *
	 * @return bool
	 */
	public function isEnabled()
	{
		return ($this->params->get('cachecleaner', 0) == 1);
	}

	public function onAfterInitialise()
	{
		$minutes = (int) $this->params->get('cache_freq', 0);

		if ($minutes <= 0)
		{
			return;
		}

		$lastJob = $this->getTimestamp('cache_clean');
		$nextJob = $lastJob + $minutes * 60;

		$now = new Date();

		if ($now->toUnix() >= $nextJob)
		{
			$this->setTimestamp('cache_clean');
			$this->purgeCache();
		}
	}

	/**
	 * Completely purges the cache
	 */
	private function purgeCache()
	{
		$er    = @error_reporting(0);
		$cache = Factory::getCache('');
		$cache->clean('sillylongnamewhichcantexistunlessyouareacompletelyparanoiddeveloperinwhichcaseyoushouldnotbewritingsoftwareokay', 'notgroup');
		@error_reporting($er);
	}
}
