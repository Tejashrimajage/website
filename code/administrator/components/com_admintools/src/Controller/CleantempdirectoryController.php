<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\Controller;

defined('_JEXEC') or die;

use Akeeba\Component\AdminTools\Administrator\Controller\Mixin\CustomACL;
use Akeeba\Component\AdminTools\Administrator\Controller\Mixin\RegisterControllerTasks;
use Akeeba\Component\AdminTools\Administrator\Controller\Mixin\ReusableModels;
use Akeeba\Component\AdminTools\Administrator\Model\CleantempdirectoryModel;
use Joomla\CMS\MVC\Controller\BaseController;

class CleantempdirectoryController extends BaseController
{
	use CustomACL;
	use RegisterControllerTasks;
	use ReusableModels;

	public function main()
	{
		/** @var CleantempdirectoryModel $model */
		$model = $this->getModel();
		$state = $model->startScanning();
		$model->setState('scanstate', $state);

		$this->display(false);
	}

	public function run()
	{
		/** @var CleantempdirectoryModel $model */
		$model = $this->getModel();
		$state = $model->run();
		$model->setState('scanstate', $state);

		$this->display(false);
	}
}