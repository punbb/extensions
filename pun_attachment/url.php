<?php

/**
 * URLs for pun_attachment.
 *
 * @copyright (C) 2008-2012 PunBB, partially based on Attachment Mod by Frank Hagstrom
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_attachment
 */

if (!defined('FORUM')) exit;

$attach_url = array(
	'admin_attachment_rules'	=>	'extensions/attachment/rules.php',
	'admin_attachment_manage'	=>	'admin/settings.php?section=pun_list_attach',
	'admin_attach_orphans'		=>	'extensions/attachment/attachment.php?section=orphans',
	'admin_attach_rules'		=>	'extensions/attachment/attachment.php?section=rules',
	'admin_attach_file'			=>	'extensions/attachment/attach_file.php',
	'admin_attach_delete'		=>	'admin/settings.php?section=list_attach&amp;action=delete&amp;id=$1&amp;csrf_token=$2',
	'admin_attach_detach'		=>	'admin/settings.php?section=list_attach&amp;action=detach&amp;id=$1&pid=$2&amp;csrf_token=$3',
	'admin_attach_rename'		=>	'admin/settings.php?section=list_attach&amp;action=rename&amp;id=$1&amp;csrf_token=$2',
	'admin_options_attach'		=>	'admin/settings.php?section=pun_attach',
	'admin_attachment_edit'		=>	'admin/settings.php?section=pun_list_attach&amp;id=$1',
	'misc_admin'				=>	'misc.php?action=pun_attachment&amp;item=$1',
	'misc_download'				=>	'misc.php?action=pun_attachment&amp;item=$1&amp;download=1',
	'misc_view'					=>	'misc.php?action=pun_attachment&amp;item=$1&amp;download=0',
	'misc_preview'				=>	'misc.php?action=pun_attachment&amp;item=$1&amp;preview',
	'misc_download_secure'		=>	'misc.php?action=pun_attachment&amp;item=$1&amp;download=1&amp;secure_str=$2',
	'misc_view_secure'			=>	'misc.php?action=pun_attachment&amp;item=$1&amp;download=0&amp;secure_str=$2',
	'misc_preview_secure'		=>	'misc.php?action=pun_attachment&amp;item=$1&amp;preview&amp;secure_str=$2',
);

?>
