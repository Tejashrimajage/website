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
use Akeeba\Component\AdminTools\Administrator\Controller\Mixin\ReusableModels;
use Joomla\CMS\MVC\Controller\FormController;

class ScanalertController extends FormController
{
	use ControllerEvents;
	use CustomACL;
	use ReusableModels;

	protected $text_prefix = 'COM_ADMINTOOLS_SCANALERT';
}