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
use Akeeba\Component\AdminTools\Administrator\Controller\Mixin\RegisterControllerTasks;
use Akeeba\Component\AdminTools\Administrator\Controller\Mixin\ReusableModels;
use Akeeba\Component\AdminTools\Administrator\Model\EmergencyofflineModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

class EmergencyofflineController extends BaseController
{
	use ControllerEvents;
	use CustomACL;
	use RegisterControllerTasks;
	use ReusableModels;

	public function main()
	{
		return $this->display(false);
	}

	public function offline()
	{
		$this->checkToken();

		/** @var EmergencyofflineModel $model */
		$model = $this->getModel();

		$status = $model->putOffline();
		$url    = Route::_('index.php?option=com_admintools', false);

		$this->setRedirect($url);

		if ($status)
		{
			$this->setMessage(Text::_('COM_ADMINTOOLS_EMERGENCYOFFLINE_LBL_APPLIED'), 'success');

			return;
		}

		$this->setMessage(Text::_('COM_ADMINTOOLS_EMERGENCYOFFLINE_ERR_NOTAPPLIED'), 'success');
	}

	public function online()
	{
		$this->checkToken();

		/** @var EmergencyofflineModel $model */
		$model  = $this->getModel();
		$status = $model->putOnline();

		$url = Route::_('index.php?option=com_admintools', false);

		$this->setRedirect($url);

		if ($status)
		{
			$this->setMessage(Text::_('COM_ADMINTOOLS_EMERGENCYOFFLINE_LBL_UNAPPLIED'), 'success');

			return;
		}

		$this->setMessage(Text::_('COM_ADMINTOOLS_EMERGENCYOFFLINE_ERR_NOTUNAPPLIED'), 'success');
	}
}