<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Joomla\Plugin\System\AdminTools\Feature;

defined('_JEXEC') || die;

class CustomBlockedRequestPage extends Base
{
	/**
	 * Shows the Admin Tools custom block message
	 */
	public function onAfterRoute()
	{
		if (!$this->app->getSession()->get('com_admintools.block', false))
		{
			return;
		}

		// This is an underhanded way to short-circuit Joomla!'s internal router.
		$input = $this->app->input;
		$input->set('option', 'com_admintools');
		$input->set('view', 'Blocks');
		$input->set('task', 'browse');
		$input->set('layout', 'default');
		$input->set('format', 'html');
	}
}
