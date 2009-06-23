<?php

/***********************************************************************

	Copyright (C) 2008  PunBB

	Partially based on Attachment Mod by Frank Hagstrom

	PunBB is free software; you can redistribute it and/or modify it
	under the terms of the GNU General Public License as published
	by the Free Software Foundation; either version 2 of the License,
	or (at your option) any later version.

	PunBB is distributed in the hope that it will be useful, but
	WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston,
	MA  02111-1307  USA

***********************************************************************/

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
	'misc_download'				=>	'misc.php?item=$1&amp;download=0',
	'misc_preview'				=>	'misc.php?item=$1&amp;preview',
	'misc_preview_secure'		=>	'misc.php?item=$1&amp;preview&amp;secure_str=$2',
	'misc_vt_down'				=>	'misc.php?item=$1&amp;download=$2',
	'misc_admin'				=>	'misc.php?item=$1',
	'misc_download_secure'		=>	'misc.php?item=$1&amp;download&amp;secure_str=$2',
	'misc_show_secure'			=>	'misc.php?item=$1&amp;show&amp;secure_str=$2',
	
);

?>
