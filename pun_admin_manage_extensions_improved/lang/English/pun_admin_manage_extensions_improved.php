<?php

/**
 * Lang file for pun_admin_manage_extensions_improved
 *
 * @copyright Copyright (C) 2008 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_admin_manage_extensions_improved
 */

if (!defined('FORUM')) die();

$lang_pun_man_ext_improved = array(
	'Reinstall fail'		=>	'You can\'t reinstall this extension, because some other extensions depend from it&nbsp;',
	'Reinstall success'		=>	'The extension was successfully reinstalled, but reported the following notices.',
	'Reinstall ext'			=>	'Reinstall \ Refresh hooks Extension %s',
	'Reinstall'				=>	'Reinstall',
	'Reinstall with deps'	=>	'<strong>Important!</strong> Extensions %s can function incorrectly, if you reinstall or refresh hooks %s extension.',
	'Extension reinstalled' =>	'The extension <strong>"%s"</strong> was successfully reinstalled.',
	'Disable selected'		=>	'Selected extensions was disabled.',
	'Enable selected'		=>	'Selected extensions was enabled.',
	'Uninstall selected'	=>	'Selected extensions was uninstalled.',
	'Input error'			=>	'Form error!',
	'Disable checked'		=>	'Disable checked extensions',
	'Enable checked'		=>	'Enable checked extensions',
	'Uninstall checked'		=>	'Uninstall checked extensions',
	'Dependency error'		=>	'<strong>Important!</strong> Some extensions can\'t work correctly without one or more selected extensions.',
	'Update error'			=>	'<strong>Important!</strong> Now available new version of %s. If you press \'continue\' extension will be updated.',	
	'Ext update'			=>	'Update extension',
	'Ignore deps'			=>	'Ignore dependencies',
	'Choose action'			=>	'Choose action',
	'Disable deps extensions'		=>	'Disable depend extensions',
	'Enable main'			=>	'Enable "main" extensions, if they have been installed.',
	'Uninstall all'			=>	'Uninstall all dependend extensions',
	'No selected'			=>	'<strong>WARNING!</strong> No extensions selected!',
	'Warnings'				=>	'<strong>WARNING!</strong> Important warnings:',
	'Work dependencies'		=>	'%s can\'t work without %s!',
	'Select extension'		=>	'Select extension',
	'Button disable'		=>	'Disable selected',
	'Button enable'			=>	'Enable selected',
	'Button uninstall'		=>	'Uninstall selected',
	'Only hooks'			=>	'Refresh hooks',
	'Force ext'				=>	'Force uninstall',
	'Force success'			=>	'Extension was forced',
	'Extension title'		=>	'Pun Manage Extensions Improved',
	'Ext note'				=>	'When there are dependencies between extensions, you will see an alert. Use refresh hooks action to remove extension hooks from database and fetch them again from manifest.xml. No install/uninstall code will be executed. Useful to keep extensions\' data untouched while debugging.'
);

?>
