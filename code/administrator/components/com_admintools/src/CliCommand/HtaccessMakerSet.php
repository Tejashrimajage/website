<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\CliCommand;

defined('_JEXEC') || die;

use Akeeba\Component\AdminTools\Administrator\CliCommand\ServerConfiguration\ServerConfigurationSet;

/**
 * admintools:htmaker:set
 *
 * Set the value of a server configuration option
 *
 */
class HtaccessMakerSet extends ServerConfigurationSet
{
	/**
	 * The default command name
	 *
	 * @var    string
	 */
	protected static $defaultName = 'admintools:htmaker:set';

	/** @var string Which engine are we working on? */
	protected $server_engine = 'htaccessmaker';
}
