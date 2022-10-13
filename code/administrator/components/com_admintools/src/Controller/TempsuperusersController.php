<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\Controller;

defined('_JEXEC') or die;

use Akeeba\Component\AdminTools\Administrator\Controller\Mixin\ControllerEvents;
use Akeeba\Component\AdminTools\Administrator\Controller\Mixin\CustomACL;
use Akeeba\Component\AdminTools\Administrator\Controller\Mixin\TempsuperuserChecks;
use Joomla\CMS\MVC\Controller\AdminController;

class TempsuperusersController extends AdminController
{
	use ControllerEvents;
	use CustomACL;
	use TempsuperuserChecks;

	protected $text_prefix = 'COM_ADMINTOOLS_TEMPSUPERUSERS';

	public function getModel($name = 'Tempsuperuser', $prefix = 'Administrator', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}

	protected function onBeforeExecute(&$task)
	{
		$this->assertNotTemporary();
	}
}