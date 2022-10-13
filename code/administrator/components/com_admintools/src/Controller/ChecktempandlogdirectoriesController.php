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
use Akeeba\Component\AdminTools\Administrator\Model\ChecktempandlogdirectoriesModel;
use Exception;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;

class ChecktempandlogdirectoriesController extends BaseController
{
	use CustomACL;
	use RegisterControllerTasks;

	public function check()
	{
		/** @var ChecktempandlogdirectoriesModel $model */
		$model = $this->getModel();

		$json['result'] = true;
		$json['msg']    = '';

		try
		{
			$folders        = $model->checkFolders();
			$folderMessages = [
				'<strong>' . Text::_('COM_ADMINTOOLS_CHECKTEMPANDLOGDIRECTORIES_LBL_TEMP_PATH') . '</strong>: <span class="font-monospace">' . $folders['tmp'] . '</span>',
				'<strong>' . Text::_('COM_ADMINTOOLS_CHECKTEMPANDLOGDIRECTORIES_LBL_LOG_PATH') . '</strong>: <span class="font-monospace">' . $folders['log'] . '</span>',
			];
			$json['msg']    = implode('<br/>', $folderMessages);
		}
		catch (Exception $e)
		{
			$json['result'] = false;
			$json['msg']    = $e->getMessage();
		}

		echo json_encode($json);

		$this->app->close();
	}
}