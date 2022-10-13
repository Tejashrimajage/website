<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\Controller;

defined('_JEXEC') or die;

use Akeeba\Component\AdminTools\Administrator\Controller\Mixin\ControllerEvents;
use Akeeba\Component\AdminTools\Administrator\Controller\Mixin\CopyAware;
use Akeeba\Component\AdminTools\Administrator\Controller\Mixin\CustomACL;
use Akeeba\Component\AdminTools\Administrator\Controller\Mixin\RegisterControllerTasks;
use Akeeba\Component\AdminTools\Administrator\Controller\Mixin\ReusableModels;
use Joomla\CMS\MVC\Controller\AdminController;

class DisallowlistsController extends AdminController
{
	use ControllerEvents;
	use CustomACL;
	use CopyAware;
	use ReusableModels;
	use RegisterControllerTasks;

	protected $text_prefix = 'COM_ADMINTOOLS_DISALLOWLISTS';

	public function getModel($name = 'Disallowlist', $prefix = 'Administrator', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}

	public function display($cachable = false, $urlparams = [])
	{
		$view        = $this->getView();
		$cpanelModel = $this->getModel('Controlpanel', 'Administrator', ['ignore_request' => true]);

		$view->setModel($cpanelModel, false);

		return parent::display($cachable, $urlparams);
	}

}