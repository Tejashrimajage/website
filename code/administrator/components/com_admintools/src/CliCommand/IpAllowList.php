<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\CliCommand;

defined('_JEXEC') || die;

use Akeeba\Component\AdminTools\Administrator\CliCommand\MixIt\ConfigureEnv;
use Akeeba\Component\AdminTools\Administrator\CliCommand\MixIt\ConfigureIO;
use Akeeba\Component\AdminTools\Administrator\CliCommand\MixIt\PrintFormattedArray;
use Akeeba\Component\AdminTools\Administrator\Model\AdminallowlistsModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * admintools:ipallow:list
 *
 */
class IpAllowList extends AbstractCommand
{
	use ConfigureEnv;
	use ConfigureIO;
	use PrintFormattedArray;
	use MVCFactoryAwareTrait;

	/**
	 * The default command name
	 *
	 * @var    string
	 */
	protected static $defaultName = 'admintools:ipallow:list';

	/**
	 * Internal function to execute the command.
	 *
	 * @param   InputInterface   $input   The input to inject into the command.
	 * @param   OutputInterface  $output  The output to inject into the command.
	 *
	 * @return  integer  The command exit code
	 *
	 * @since   7.5.0
	 */
	protected function doExecute(InputInterface $input, OutputInterface $output): int
	{
		$this->configureEnv();
		$this->configureSymfonyIO($input, $output);

		$format = (string) ($this->cliInput->getOption('format') ?? 'table');

		/** @var AdminallowlistsModel $model */
		$model = $this->getMVCFactory()->createModel('Adminallowlists', 'Administrator');

		$model->setState('list.start', 0);
		$model->setState('list.limit', 0);
		$model->setState('list.ordering', 'id');
		$model->setState('list.direction', 'desc');

		$rows   = $model->getItems();

		// Convert everything into an array, so we can easily handel format=table, too
		$output = array_map(function($row){
			return (array) $row;
		}, $rows);

		return $this->printFormattedAndReturn($output, $format);
	}

	/**
	 * Configure the command.
	 *
	 * @return  void
	 *
	 * @since   7.5.0
	 */
	protected function configure(): void
	{
		$this->addOption('format', null, InputOption::VALUE_OPTIONAL, Text::_('COM_ADMINTOOLS_CLI_COMMON_LIST_OPT_FORMAT'), 'table');

		$this->setDescription(Text::_('COM_ADMINTOOLS_CLI_IPALLOW_LIST_DESC'));
		$this->setHelp(Text::_('COM_ADMINTOOLS_CLI_IPALLOW_LIST_HELP'));
	}
}
