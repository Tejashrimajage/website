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
use Akeeba\Component\AdminTools\Administrator\Controller\Mixin\SendTroubleshootingEmail;
use Akeeba\Component\AdminTools\Administrator\Model\AdminpasswordModel;
use Exception;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Router\Route;
use Joomla\Input\Input;

class AdminpasswordController extends BaseController
{
	use ControllerEvents;
	use CustomACL;
	use SendTroubleshootingEmail;
	use RegisterControllerTasks;

	public function __construct($config = [], MVCFactoryInterface $factory = null, ?CMSApplication $app = null, ?Input $input = null)
	{
		$config['default_task'] = $config['default_task'] ?? 'main';

		parent::__construct($config, $factory, $app, $input);

		$this->registerControllerTasks('main');
	}

	public function main()
	{
		$this->display(false);
	}

	/**
	 * Enabled administrator directory password protection
	 *
	 * @throws Exception
	 */
	public function protect()
	{
		$this->checkToken();

		$redirectUrl = Route::_('index.php?option=com_admintools&view=Adminpassword', false);
		$this->setRedirect($redirectUrl);

		/** @var AdminpasswordModel $model */
		$model = $this->getModel();

		$username        = $model->getState('username');
		$password        = $model->getState('password');
		$password2       = $this->input->getRaw('password2');
		$resetErrorPages = $model->getState('resetErrorPages');
		$mode            = $model->getState('mode');

		if (!in_array($mode, ['joomla', 'php', 'everything']))
		{
			$mode = 'everything';
		}

		if (empty($username))
		{
			$this->setMessage(Text::_('COM_ADMINTOOLS_ADMINPASSWORD_ERR_NOUSERNAME'), 'error');

			return;
		}

		if (empty($password))
		{
			$this->setMessage(Text::_('COM_ADMINTOOLS_ADMINPASSWORD_ERR_NOPASSWORD'), 'error');

			return;
		}

		if ($password != $password2)
		{
			$this->setMessage(Text::_('COM_ADMINTOOLS_ADMINPASSWORD_ERR_PASSWORDNOMATCH'), 'error');

			return;
		}

		$this->sendTroubelshootingEmail($this->getName());

		$model->setState('username', $username);
		$model->setState('password', $password);
		$model->setState('resetErrorPages', $resetErrorPages);
		$model->setState('mode', $mode);

		$status = $model->protect();

		if ($status)
		{
			$this->setMessage(Text::_('COM_ADMINTOOLS_ADMINPASSWORD_LBL_APPLIED'), 'success');

			$this->app->setUserState('com_admintools.adminpassword.username', null);
			$this->app->setUserState('com_admintools.adminpassword.password', null);
			$this->app->setUserState('com_admintools.adminpassword.resetErrorPages', null);
			$this->app->setUserState('com_admintools.adminpassword.mode', null);

			return;
		}

		$this->setMessage(Text::_('COM_ADMINTOOLS_ADMINPASSWORD_ERR_NOTAPPLIED'), 'error');
	}

	public function unprotect()
	{
		$this->checkToken('get');

		$redirectUrl = Route::_('index.php?option=com_admintools&view=Adminpassword', false);
		$this->setRedirect($redirectUrl);

		/** @var AdminpasswordModel $model */
		$model  = $this->getModel();
		$status = $model->unprotect();

		if ($status)
		{
			$this->setMessage(Text::_('COM_ADMINTOOLS_ADMINPASSWORD_LBL_UNAPPLIED'), 'success');

			return;
		}

		$this->setMessage(Text::_('COM_ADMINTOOLS_ADMINPASSWORD_ERR_NOTUNAPPLIED'), 'error');
	}
}