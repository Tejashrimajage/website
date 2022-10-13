<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Joomla\Plugin\Console\AdminTools\Command;

defined('_JEXEC') || die;

use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\Console\Command\AbstractCommand;

class CommandFactory implements CommandFactoryInterface
{
	use MVCFactoryAwareTrait;

	public function getCLICommand(string $commandName): AbstractCommand
	{
		$classFQN = 'Akeeba\\Component\\AdminTools\\Administrator\\CliCommand\\' . ucfirst($commandName);

		if (!class_exists($classFQN))
		{
			throw new \RuntimeException(sprintf('Unknown Admin Tools CLI command class ‘%s’.', $commandName));
		}

		$classParents = class_parents($classFQN);

		if (!in_array(AbstractCommand::class, $classParents))
		{
			throw new \RuntimeException(sprintf('Invalid Admin Tools CLI command object ‘%s’.', $commandName));
		}

		$o = new $classFQN;

		if (method_exists($classFQN, 'setMVCFactory'))
		{
			$o->setMVCFactory($this->getMVCFactory());
		}

		return $o;
	}
}