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
use Joomla\CMS\MVC\Controller\FormController;

class TempsuperuserController extends FormController
{
	use ControllerEvents;
	use CustomACL;
	use TempsuperuserChecks;

	protected $text_prefix = 'COM_ADMINTOOLS_TEMPSUPERUSER';

	protected function allowEdit($data = [], $key = 'id')
	{
		$this->assertNotTemporary();

		$pk = $data[$key] ?? null;

		$this->assertNotMyself($pk);

		return parent::allowEdit($data, $key);
	}

	protected function allowAdd($data = [])
	{
		$this->assertNotTemporary();

		return parent::allowAdd($data);
	}

	protected function allowSave($data, $key = 'id')
	{
		$this->assertNotTemporary();

		$pk = $data[$key] ?? null;

		$this->assertNotMyself($pk);

		return parent::allowSave($data, $key);
	}


}