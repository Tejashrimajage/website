<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Plugin\Console\AdminTools\Command\CommandFactoryInterface;
use Joomla\Plugin\Console\AdminTools\Command\CommandFactoryProvider;
use Joomla\Plugin\Console\AdminTools\Extension\AdminTools;

// Make sure that Joomla has registered the namespace for the plugin
if (!class_exists('\Joomla\Plugin\Console\AdminTools\Extension\AdminTools'))
{
	JLoader::registerNamespace('\Joomla\Plugin\Console\AdminTools', realpath(__DIR__ . '/../src'));
}

return new class implements ServiceProviderInterface {
	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  void
	 *
	 * @since   7.0.0
	 */
	public function register(Container $container)
	{
		$container->registerServiceProvider(new MVCFactory('Akeeba\\Component\\AdminTools'));
		$container->registerServiceProvider(new CommandFactoryProvider());

		$container->set(
			PluginInterface::class,
			function (Container $container) {
				$config     = (array) PluginHelper::getPlugin('console', 'admintools');
				$subject    = $container->get(DispatcherInterface::class);

				$factory = $container->get(CommandFactoryInterface::class);

				return new AdminTools($subject, $factory, $config);
			}
		);
	}
};
