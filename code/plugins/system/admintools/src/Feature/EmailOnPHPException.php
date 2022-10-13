<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Joomla\Plugin\System\AdminTools\Feature;

defined('_JEXEC') || die;

use Joomla\CMS\Event\ErrorEvent;
use Joomla\CMS\Exception\ExceptionHandler;
use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\Plugin\System\AdminTools\Utility\RescueUrl;
use PHPMailer\PHPMailer\Exception;

class EmailOnPHPException extends Base
{
	private $emailAddress;

	/**
	 * Is this feature enabled?
	 *
	 * @return bool
	 */
	public function isEnabled()
	{
		$this->emailAddress = trim($this->wafParams->getValue('emailphpexceptions', '') ?: '');

		return ($this->emailAddress != '');
	}

	public function onError(ErrorEvent $event)
	{
		if (empty($this->emailAddress))
		{
			return;
		}

		$error = $event->getError();
		$app   = $event->getApplication();
		$code  = (int) $error->getCode();

		// Do not handle "Not found" and "Forbidden" exceptions
		if ($code == 403 || $code == 404)
		{
			return;
		}

		$type    = get_class($error);
		$subject = 'Unhandled exception - ' . $type;

		// Now let's htmlencode the dump of all superglobals
		$get     = htmlentities(print_r($_GET, true));
		$post    = htmlentities(print_r($_POST, true));
		$cookie  = htmlentities(print_r($_COOKIE, true));
		$request = htmlentities(print_r($_REQUEST, true));
		$server  = htmlentities(print_r($_SERVER, true));

		$body = <<<HTML
<p>A PHP Exception occurred on your site. Here you can find the stack trace:</p>
<p>
	Exception Type: <code>$type</code><br/>
	File: {$error->getFile()}<br/>
	Line: {$error->getLine()}<br/>
	Message: {$error->getMessage()} 
</p>
<pre>{$error->getTraceAsString()}</pre>

<h3>Request information</h3>
<h4>GET variables</h4>
<pre>$get</pre>
<h4>POST variables</h4>
<pre>$post</pre>
<h4>COOKIE variables</h4>
<pre>$cookie</pre>
<h4>REQUEST variables</h4>
<pre>$request</pre>
<h4>SERVER variables</h4>
<pre>$server</pre>
HTML;

		$recipients = is_array($this->emailAddress) ? $this->emailAddress : explode(',', $this->emailAddress);
		$recipients = array_map('trim', $recipients);

		foreach ($recipients as $recipient)
		{
			try
			{
				$mailer = Factory::getMailer();
				$mailer->sendMail(
					$app->get('mailfrom'),
					$app->get('fromname'),
					$recipient,
					$subject,
					$body,
					true
				);
			}
			catch (Exception $e)
			{
				// Do nothing
			}
		}
	}
}
