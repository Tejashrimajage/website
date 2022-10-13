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
use Joomla\CMS\MVC\Controller\FormController;

class UrlredirectionController extends FormController
{
	use ControllerEvents;
	use CustomACL;

	protected $text_prefix = 'COM_ADMINTOOLS_URLREDIRECTION';
}