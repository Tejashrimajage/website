<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Joomla\Plugin\System\AdminTools\Feature;

defined('_JEXEC') || die;

use ReflectionProperty;

class ItemidShield extends Base
{
	/**
	 * Is this feature enabled?
	 *
	 * @return bool
	 */
	public function isEnabled()
	{
		if (!$this->app->isClient('site'))
		{
			return false;
		}

		if ($this->skipFiltering)
		{
			return false;
		}

		return ($this->wafParams->getValue('itemidshield', 2) != 0);
	}

	/**
	 * Check if Itemid contains a valid value (intergers only)
	 */
	public function onAfterRoute()
	{
		$waf_value = $this->wafParams->getValue('itemidshield', 2);

		// Always cast it to a string. If we're fetching a default value from the database (ie the homepage) it will be
		// an integer instead of a string
		$itemid    = (string)$this->input->get('Itemid', '', 'raw');

		if (!$itemid || !$waf_value)
		{
			return;
		}

		$is_valid = (string)(intval($itemid)) === $itemid;

		if ($is_valid)
		{
			return;
		}

		if ($waf_value == 2)
		{
			$cleaned = intval($itemid);
			$this->input->set('Itemid', $cleaned);

			return;
		}

		// If I'm here, it means that I have to block and log the request
		$this->exceptionsHandler->blockRequest('itemidshield');
	}
}
