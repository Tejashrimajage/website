<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var  \Akeeba\Component\AdminTools\Administrator\View\Controlpanel\HtmlView $this */

$root      = realpath(JPATH_ROOT) ?: '';
$root      = trim($root);
$emptyRoot = empty($root);

?>
<?php if(isset($this->jwarnings) && !empty($this->jwarnings)): ?>
	<div class="alert alert-danger">
		<h3 class="alert-heading">
			<span class="icon-exclamation-triangle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('ERROR'); ?></span>
			<?=Text::_('COM_ADMINTOOLS_CONTROLPANEL_ERR_JCONFIG'); ?>
		</h3>
		<p><?= $this->jwarnings ?></p>
	</div>
<?php endif; ?>

<?php if(isset($this->frontEndSecretWordIssue) && !empty($this->frontEndSecretWordIssue)): ?>
	<div class="alert alert-danger">
		<h3 class="alert-heading">
			<span class="icon-exclamation-triangle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('ERROR'); ?></span>
			<?= Text::_('COM_ADMINTOOLS_CONTROLPANEL_ERR_FESECRETWORD_HEADER'); ?>
		</h3>
		<p>
			<?= Text::_('COM_ADMINTOOLS_CONTROLPANEL_ERR_FESECRETWORD_INTRO'); ?>
		</p>
		<p>
			<?= $this->frontEndSecretWordIssue; ?>
		</p>
		<p>
			<?= Text::_('COM_ADMINTOOLS_CONTROLPANEL_ERR_FESECRETWORD_WHATTODO_JOOMLA'); ?>
			<?= Text::sprintf('COM_ADMINTOOLS_CONTROLPANEL_ERR_FESECRETWORD_WHATTODO_COMMON', $this->newSecretWord); ?>
		</p>
		<p>
			<a class="btn btn-success"
			   href="<?= Route::_(sprintf('index.php?option=com_admintools&view=Controlpanel&task=resetSecretWord&%s=1', Factory::getApplication()->getFormToken())) ?>">
				<span class="fa fa-sync-alt"></span>
				<?=Text::_('COM_ADMINTOOLS_CONTROLPANEL_BTN_FESECRETWORD_RESET'); ?>
			</a>
		</p>
	</div>
<?php endif; ?>

<?php /* Obsolete PHP version check */ ?>
<?= $this->loadAnyTemplate('Controlpanel/phpversion_warning', false, [
	'softwareName'          => 'Admin Tools',
	'minPHPVersion'         => '7.2.0',
	'class_priority_low'    => 'alert alert-info',
	'class_priority_medium' => 'alert alert-warning text-dark',
	'class_priority_high'   => 'alert alert-danger',
]); ?>

<?php if ($emptyRoot): ?>
	<div class="alert alert-danger">
		<span class="icon-exclamation-triangle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('ERROR'); ?></span>
		<?=Text::_('COM_ADMINTOOLS_CONTROLPANEL_LBL_EMPTYROOT'); ?>
	</div>
<?php endif; ?>

<?php if( $this->needsdlid):
	$updateSiteEditUrl = Route::_('index.php?option=com_installer&task=updatesite.edit&update_site_id=' . $this->updateSiteId)
	?>
	<div class="alert alert-info alert-dismissible">
		<h3 class="alert-heading">
			<?=Text::_('COM_ADMINTOOLS_CONTROLPANEL_LBL_MUSTENTERDLID'); ?>
			<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?= Text::_('JLIB_HTML_BEHAVIOR_CLOSE') ?>"></button>
		</h3>
		<p>
			<?=Text::sprintf('COM_ADMINTOOLS_CONTROLPANEL_LBL_NEEDSDLID', 'https://www.akeeba.com/download/official/add-on-dlid.html'); ?>
		</p>
		<p>
			<?= Text::sprintf('COM_ADMINTOOLS_CONTROLPANEL_MSG_WHERETOENTERDLID', $updateSiteEditUrl) ?>
		</p>
		<p class="text-muted">
			<?= Text::_('COM_ADMINTOOLS_CONTROLPANEL_MSG_JOOMLABUGGYUPDATES') ?>
		</p>
	</div>
<?php endif; ?>

<?php if($this->serverConfigEdited): ?>
	<div class="alert alert-warning">
		<p class="text-dark">
			<?=Text::_('COM_ADMINTOOLS_CONTROLPANEL_LBL_SERVERCONFIGWARN'); ?>
		</p>
		<p>
			<a href="<?= Route::_('index.php?option=com_admintools&view=Controlpanel&task=regenerateServerConfig') ?>"
			   class="btn btn-success">
				<span class="fa fa-check"></span>
				<?= Text::_('COM_ADMINTOOLS_CONTROLPANEL_LBL_SERVERCONFIGWARN_REGENERATE'); ?>
			</a>
			<a href="<?= Route::_('index.php?option=com_admintools&view=Controlpanel&task=ignoreServerConfigWarn') ?>"
			   class="btn btn-outline-danger">
				<span class="fa fa-eye-slash"></span>
				<?=Text::_('COM_ADMINTOOLS_CONTROLPANEL_LBL_SERVERCONFIGWARN_IGNORE'); ?>
			</a>
		</p>
	</div>
<?php endif; ?>
