<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\View\Blockedrequestslog;

defined('_JEXEC') or die;

use Akeeba\Component\AdminTools\Administrator\Model\BlockedrequestslogsModel;
use Akeeba\Component\AdminTools\Administrator\View\Mixin\TaskBasedEvents;
use Joomla\CMS\MVC\View\JsonView as BaseJsonView;

class JsonView extends BaseJsonView
{
	use TaskBasedEvents;

	public function onBeforeMain()
	{
		/** @var BlockedrequestslogsModel $model */
		$model = $this->getModel();

		echo json_encode($model->getItems());
	}
}