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
use Joomla\CMS\MVC\Controller\AdminController;

class UrlredirectionsController extends AdminController
{
	use ControllerEvents;
	use CustomACL;
	use CopyAware;

	protected $text_prefix = 'COM_ADMINTOOLS_URLREDIRECTIONS';

	public function getModel($name = 'Urlredirection', $prefix = 'Administrator', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}
}