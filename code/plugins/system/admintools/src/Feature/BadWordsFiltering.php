<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Joomla\Plugin\System\AdminTools\Feature;

defined('_JEXEC') || die;

use Joomla\Plugin\System\AdminTools\Utility\Cache;

class BadWordsFiltering extends Base
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

		return ($this->wafParams->getValue('antispam', 0) == 1);
	}

	/**
	 * The simplest anti-spam solution imaginable. Just blocks a request if a prohibited word is found.
	 */
	public function onAfterRoute()
	{
		$badwords = Cache::getCache('badwords');

		if (empty($badwords))
		{
			return;
		}

		$hashes = ['get', 'post'];

		foreach ($hashes as $hash)
		{
			$input   = $this->input->$hash;
			$allVars = $input->getArray();

			if (empty($allVars))
			{
				continue;
			}

			foreach ($badwords as $word)
			{
				$regex = '#\b' . $word . '\b#i';

				if ($this->recursiveRegExMatch($regex, $allVars, true))
				{
					$extraInfo = "Hash      : $hash\n";
					$extraInfo .= "Variables :\n";
					$extraInfo .= print_r($allVars, true);
					$extraInfo .= "\n";
					$this->exceptionsHandler->blockRequest('antispam', null, $extraInfo);
				}
			}
		}
	}
}
