<?php
/**
 * @package 	Cloud Panel Component for Joomla!
 * @author 		CloudAccess.net LCC
 * @copyright 	(C) 2010 - CloudAccess.net LCC
 * @license 	GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

//!no direct access
defined('_JEXEC') or die ('Restricted access');

jimport('joomla.plugin.plugin');

//only set if request by stripe
if (class_exists('JInput')) {
	$input = JFactory::getApplication()->input;

	if ($input->getInt('caupdate',0) || $input->getInt('cacheckstate',0)) {
		//default repsonse function
		function stripeResponse($response)
		{
			$app = JFactory::getApplication();
			$callback = trim($app->input->getCmd('callback', ''));

			if (!empty($callback)) {
				$tempalte = $callback.'(%s)';
			} else {
				$template = '%s';
			}

			// force header 200 if catch error
			header("HTTP/1.0 200");
			echo sprintf($template, json_encode($response));
			$app->close();
		}
	}


	if ($input->getString('format','html') == 'json' && $input->getInt('caupdate',0)) {
		//transform fatal error in json response for stripe
		function catchPhpStripeErrors() {
			$error = error_get_last();
			// Catch php errors: E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR and E_RECOVERABLE_ERROR
			// http://www.php.net/manual/en/errorfunc.constants.php
			if (in_array($error['type'], array(1,16,64,4096))) {
				$response['code'] = 66;
				$response['stats'] = 'failure';

				$response['message'] = $error['message'].' at '.$error['file'].' line '.$error['line'];

				$app = JFactory::getApplication();
				$ok = ($app->input->getInt('caupdate',0) || $app->input->getInt('cacheckstate',0)) ? true : false ;

				if ($ok)
				{
					stripeResponse($response);
				}
			}
		}
		register_shutdown_function('catchPhpStripeErrors');

		// import required core classes
		jimport('joomla.application.component.controller');
		jimport('joomla.application.component.model');
		jimport('joomla.application.component.helper');
		jimport('legacy.component.helper');
		jimport('joomla.filesystem.archive');
		jimport('joomla.filesystem.path');
		jimport('joomla.filesystem.folder');
		jimport('joomla.filesystem.file');
		jimport('joomla.client.helper');

		// compatibility layer
		if (JVERSION >= '3.0')
		{
			class_alias('JModelLegacy', 'CAModel');
			class_alias('JControllerLegacy', 'CAController');
		}
		else
		{
			class_alias('JModel', 'CAModel');
			class_alias('JController', 'CAController');
		}

		// add models path
		define('CA_BASEPATH', __DIR__.'/update');
		CAModel::addIncludePath(CA_BASEPATH.'/models');
		CAModel::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_joomlaupdate/models/');
		require_once CA_BASEPATH . '/controller.json.php';

		JLoader::register('JAdministrator', JPATH_ADMINISTRATOR.'/includes/application.php');
		ini_set('display_errors','0');
		ob_start();
	}
}

/**
 * Class plgSystemCloudlogin
 *
 * @since       5.0
 */
class plgSystemCloudPanel extends JPlugin
{
	/**
	 * User Object
	 *
	 * @since 	5.0
	 */
	private $user = null;

	/**
	 * Joomla Application
	 *
	 * @since 	5.0
	 */
	public $app = null;

	/**
	 * Api url
	 *
	 * @since 	5.0
	 */
	private $api_url_param = 'ca_sso_link';

	/**
	 * @since   5.0
	 */
	public function onAfterInitialise()
	{
		$this->user = JFactory::getUser();
		$this->app = JFactory::getApplication();
		if (class_exists('JRequest')) {
			$token = JRequest::getString('catoken', '');
			$isca  = JRequest::getInt('ccp', 0);
		} else {
			$token = trim($this->app->input->getString('catoken',''));
			$isca  = trim($this->app->input->getInt('ccp',0));
		}

		//only validate token if user are guest
		if ($this->user->guest && !empty($token) && $isca) {
			$this->checkToken($token);
		}

		if (defined('CA_BASEPATH')) {
			$comJoomlaupdate = JComponentHelper::getComponent('com_joomlaupdate');

			if ($comJoomlaupdate->id == 0) {
				$response = array('stats' => 'failure', 'code' => 2, 'message' => 'Joomla update component not found in extensions table');
				stripeResponse($response);
			}

			if (!is_dir(JPATH_ADMINISTRATOR.'/components/com_joomlaupdate/') || intval($this->getCoreUpdateSiteId()) == 0)
			{
				$response = array(
					'stats' => 'failure',
					'code' => '2',
					'message' => 'Your Joomla dont support update method'
				);
				stripeResponse($response);
			}

			//remove restoration file if exists
			$restoration_file_path = JPATH_ADMINISTRATOR.'/components/com_joomlaupdate/restoration.php';
			if (file_exists($restoration_file_path)) {
				JFile::delete($restoration_file_path);
			}

			$this->setupUpdateSite();

			$this->dispatchUpdate(trim($this->app->input->getCmd('task','start')));
		}
	}

	/**
	 * Setup joomla update
	 *
	 * @since 	5.0
	 */
	private function setupUpdateSite()
	{
		// for 2.5 we always use LTS
		if (JVERSION <= 3.0)
		{
			$joomlaupdate = JComponentHelper::getParams('com_joomlaupdate');
			//set ALWAYS to LTS default
			$joomlaupdate->set('updatesource','lts');

			$db = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->update('#__extensions AS e')->set('e.params = '.$db->quote($joomlaupdate->toString('json')))->where('e.element="com_joomlaupdate"');
			$db->setQuery($query);
			$db->query();

			CAModel::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_joomlaupdate/models/');
			$modelJoomlaUpdate = CAModel::getInstance('default','joomlaupdateModel');
			$modelJoomlaUpdate->applyUpdateSite();
		}
		// 3.0
		if (JVERSION >= '3.0')
		{
			$joomlaupdate = JComponentHelper::getParams('com_joomlaupdate');
			$siteUpdateSource = $joomlaupdate->get('updatesource','lts');

			if (JVERSION == '3.0.0') {
				//check if already installed
				$db = JFactory::getDbo();

				$query = $db->getQuery(true);
				$query->select('extension_id')->from('#__extensions')->where('element="joomlashort"');
				$db->setQuery($query);
				$extension_id = $db->loadResult();

				if (is_null($extension_id) || empty($extension_id))
				{
					// Unpack the downloaded package file
					$config = new JConfig;
					$tmp_dest = $config->tmp_path.DIRECTORY_SEPARATOR.'joomla_3-0-0_hotpatch.zip';

					if (!is_file($tmp_dest)) {
						$response = array('stats' => 'failure', 'code' => 64, 'message' => 'hotpatch for 3.0.0 not found');
						stripeResponse($response);
					}

					$package = JInstallerHelper::unpack($tmp_dest);

					// Was the package unpacked?
					if (!$package) {
						$response = array('stats' => 'failure', 'code' => 64, 'message' => 'hotpatch for 3.0.0 not found');
						stripeResponse($response);
					}

					// Get an installer instance
					$installer = JInstaller::getInstance();

					// Install the package
					if (!$installer->install($package['dir'])) {
						$response = array('stats' => 'failure', 'code' => 65, 'message' => 'cant install hotpatch');
						stripeResponse($response);
					}

					// Cleanup the install files
					if (!is_file($package['packagefile'])) {
						$config = JFactory::getConfig();
						$package['packagefile'] = $config->get('tmp_path') . '/' . $package['packagefile'];
					}

					JInstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);

					//set to sts
					$joomlaupdate->set('updatesource','custom');
					$joomlaupdate->set('customurl','http://extensions.cloudaccess.net/core/list.xml');

					$db = JFactory::getDbo();
					$query = $db->getQuery(true);
					$query->update('#__extensions AS e')->set('e.params = '.$db->quote($joomlaupdate->toString('json')))->where('e.element="com_joomlaupdate"');
					$db->setQuery($query);
					$db->query();

					CAModel::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_joomlaupdate/models/');
					$modelJoomlaUpdate = CAModel::getInstance('default','joomlaupdateModel');
					$modelJoomlaUpdate->applyUpdateSite();
				}
			} else if (JVERSION < 3.2) {
				//set to lts for 3.1
				$joomlaupdate->set('updatesource','custom');
				$joomlaupdate->set('customurl','http://extensions.cloudaccess.net/core/list.xml');

				$db = JFactory::getDbo();
				$query = $db->getQuery(true);
				$query->update('#__extensions AS e')->set('e.params = '.$db->quote($joomlaupdate->toString('json')))->where('e.element="com_joomlaupdate"');
				$db->setQuery($query);
				$db->query();

				CAModel::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_joomlaupdate/models/');
				$modelJoomlaUpdate = CAModel::getInstance('default','joomlaupdateModel');
				$modelJoomlaUpdate->applyUpdateSite();
			} else {
				//force to lts
				$joomlaupdate->set('updatesource','lts');

				$db = JFactory::getDbo();
				$query = $db->getQuery(true);
				$query->update('#__extensions AS e')->set('e.params = '.$db->quote($joomlaupdate->toString('json')))->where('e.element="com_joomlaupdate"');
				$db->setQuery($query);
				$db->query();
			}
		}
	}

	/**
	 * Dispatch Update Action
	 *
	 * @since 	5.0
	 */
	private function dispatchUpdate()
	{
		$task = $this->app->input->getCmd('task', 'start');
		$controller = new updateController(array('base_path' => CA_BASEPATH));
		if (!method_exists($controller, $task)) {
			$response = array(
				'stats' => 'failure',
				'code' => '2',
				'message' => 'Plugin dont support '.$task.' method'
			);
			stripeResponse($response);
		} else {
			ob_end_clean();
			$controller->execute($task);
			$this->app->close();
		}
	}

	/**
	 * Return Core Update Site Id
	 *
	 * @since 	5.0
	 */
	private function getCoreUpdateSiteId()
	{
		$db = JFactory::getDbo();
		$db->setQuery($db->getQuery(true)->select('update_site_id')->from('#__update_sites')->where('name="Joomla Core"'));
		return $db->loadResult();
	}

	/**
	 * Validate token and autologin
	 *
	 * @since 	5.0
	 */
	private function checkToken($token)
	{
		$api_url = $this->params->get($this->api_url_param, 'https://sso.cloudaccess.net');

		// use curl for request
		$response = new stdclass;
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL, $api_url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch,CURLOPT_POST, true);
		curl_setopt($ch,CURLOPT_POSTFIELDS, 'token='.$token);
		$response->body = curl_exec($ch);
		$response->code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($response->code != 200) {
			$this->app->enqueueMessage('cloudaccess auhtentication: service not available','error');
		}

		if (empty($response->body)) {
			return;
		}

		try {
			$credentials = json_decode($response->body, true);

			if (!$credentials['success']) {
				$this->app->redirect('index.php', $credentials['error'],'error');
			}

			$result = $this->app->login($credentials['data']);

			if ((!$result && !is_null($result)) || (is_null($result) && !$this->user->id)) {
				$this->app->enqueueMessage('User not logged in / Insufficient permissions','error');
			}
		} catch (Exception $e) {
			$this->app->enqueueMessage('cloudaccess authentication: Invalid response','error');
		}
	}

	/**
	 * @since 	5.0
	 */
	public function onAfterRender()
	{
		// void for 1.5
		if (!class_exists('JInput')) {
			return;
		}

		// response after render for check state
		if ($this->app->input->getInt('cacheckstate',0))
		{
			$response = array(
				'stats' => 'ok',
				'code' => '200',
				'message' => 'No syntax errors detected'
			);
			stripeResponse($response);
		}
	}
}
