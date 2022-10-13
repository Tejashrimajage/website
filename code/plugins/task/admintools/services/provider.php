<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Plugin\Task\AdminTools\Extension\AdminTools;

return new class implements ServiceProviderInterface {
	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  void
	 *
	 * @since   7.1.2
	 */
	public function register(Container $container)
	{
		if (!ComponentHelper::isEnabled('com_admintools'))
		{
			return;
		}

		$container->registerServiceProvider(new MVCFactory('Akeeba\\Component\\AdminTools'));

		$container->set(
			PluginInterface::class,
			function (Container $container) {
				$plugin     = PluginHelper::getPlugin('task', 'admintools');
				$dispatcher = $container->get(DispatcherInterface::class);

				return new AdminTools(
					$dispatcher,
					(array) $plugin
				);
			}
		);
	}
};
