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
use Akeeba\Component\AdminTools\Administrator\Model\BlockedrequestslogsModel;
use Exception;
use Joomla\CMS\Document\JsonDocument;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Router\Route;

class BlockedrequestslogController extends AdminController
{
	use ControllerEvents;
	use CustomACL;
	use ReusableModels;

	protected $text_prefix = 'COM_ADMINTOOLS_LOG';

	public function getModel($name = 'Blockedrequestslog', $prefix = '', $config = [])
	{
		return parent::getModel($name, $prefix, $config);
	}

	public function display($cachable = false, $urlparams = [])
	{
		if ($this->app->getDocument() instanceof JsonDocument)
		{
			$model = $this->getModel('Blockedrequestslogs', '', ['ignore_request' => true]);

			$model->setState('groupbydate', $this->input->getInt('groupbydate'));
			$model->setState('groupbytype', $this->input->getInt('groupbytype'));
			$model->setState('datefrom', $this->input->getString('datefrom'));
			$model->setState('dateto', $this->input->getString('dateto'));
			$model->setState('ip', $this->input->getString('ip'));
			$model->setState('url', $this->input->getString('url'));
			$model->setState('reason', $this->input->getString('reason'));

			$limit = $this->input->getInt('limit', 20);
			$model->setState('list.limit', $limit);

			$value      = $this->input->getInt('limitstart', 0);
			$limitstart = ($limit != 0 ? (floor($value / $limit) * $limit) : 0);
			$model->setState('list.start', $limitstart);
		}
		else
		{
			$model = $this->getModel('Blockedrequestslogs', 'Administrator');
		}

		$view = $this->getView();
		$view->setModel($model, true);

		$cPanelModel = $this->getModel('Controlpanel', 'Administrator');
		$view->setModel($cPanelModel, false);

		$view->document = $this->app->getDocument();

		$view->display();

		return $this;
	}


	public function ban()
	{
		$this->checkToken('request');

		$url     = Route::_('index.php?option=com_admintools&view=Blockedrequestslog', false);
		$msg     = Text::_('COM_ADMINTOOLS_DISALLOWLIST_LBL_SAVED');
		$msgType = 'success';

		try
		{
			$id = $this->input->getString('id', '');

			if (empty($id))
			{
				throw new Exception(Text::_('COM_ADMINTOOLS_LOG_ERR_NOID'), 500);
			}

			/** @var BlockedrequestslogsModel $model */
			$model = $this->getModel('Blockedrequestslogs', 'Administrator');

			if (!$model->ban($id))
			{
				throw new Exception($model->getError());
			}
		}
		catch (Exception $e)
		{
			$msg     = $e->getMessage();
			$msgType = 'error';
		}

		$this->setRedirect($url, $msg, $msgType);
	}

	public function unban()
	{
		$this->checkToken('request');

		$url     = Route::_('index.php?option=com_admintools&view=Blockedrequestslog', false);
		$msg     = Text::_('COM_ADMINTOOLS_DISALLOWLIST_LBL_DELETED');
		$msgType = 'success';

		try
		{
			$id = $this->input->getString('id', '');

			if (empty($id))
			{
				throw new Exception(Text::_('COM_ADMINTOOLS_LOG_ERR_NOID'), 500);
			}

			/** @var BlockedrequestslogsModel $model */
			$model = $this->getModel('Blockedrequestslogs', 'Administrator');
			$model->unban($id);
		}
		catch (Exception $e)
		{
			$msg     = $e->getMessage();
			$msgType = 'error';
		}

		$this->setRedirect($url, $msg, $msgType);
	}
}