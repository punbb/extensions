<?php

/***********************************************************************

	Copyright (C) 2009  PunBB

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

if (!defined('FORUM')) die();

// Language definitions for frequently used strings
$lang_attach = array(
//admin
'Attach part head'		=>	'<strong>%s.</strong> User attachments',
'Icon part head'		=>	'<strong>%s.</strong> Icon settings',
'Display small'			=>	'Displaying small images, which size is smaller than parameters below.',
'Disable attachments'	=>	'Disable attachments',
'Display icons' 		=>	'Enable displaying icons',
'Create orphans'		=>	'Enable it if you want to create orphans.',
'Always deny'			=>	'Always deny',
'Filesize'				=>	'Filesize',
'Filename'				=>	'Filename',
'Max filesize'			=>	'Max filesize',
'Max height'			=>	'Small images max height',
'Max width'				=>	'Small images max width',
'Manage icons'			=>	'Manage icons',
'Main options'			=>	'Main options',
'Attachment rules'		=>	'Attachment rules',
'Attachment page head'	=>	'Attachment <strong>%s</strong>',
'Delete button'			=>	'Delete',
'Attach button'			=>	'Attach',
'Rename button'			=>	'Rename',
'Detach button'			=>	'Detach',
'Uploaded date'			=>	'Uploaded date',
'MIME-type'				=>	'MIME-type',
'Post id'				=>	'Post id',
'Downloads'				=>	'Downloads',
'New name'				=>	'New name',
'Ascending'				=>	'Ascending',
'Descending'			=>	'Descending',

'Orphans help'			=>	'If this option is enabled, attachments will not be removed from the database, when a user wants to delete a post with these attachments.',
'Icons help'			=>	'Icons for attachment types are stored in ../extensions/attachment/img/ To add or change icons, use the following form. In cells of the first coloumn enter type and in opposed cell enter icon name. PunBB allowed you to use icons of the png, gif, jpeg, ico types.',


// la
'Attachment'			=>	'Attachment',
'Size:'					=>	'Size:',
'bytes'					=>	'bytes',
'Downloads:'			=>	'Downloads:',
'Kbytes'				=>	' kbytes',
'Mbytes'				=>	' mbytes',
'Bytes'					=>	' bytes',
'Kb'					=>	' kb',
'Mb'					=>	' mb',
'B'						=>	' b',
'Since'					=>	'%s downloads since %s',
'Never download'		=>	'file has never been downloaded.',
'Since (title)'			=>	'%s times downloaded since %s',
'Attachment icon'		=>	'Attachment icon',

//post.php
'Attachment'			=>	'Attachment',	// Used in post the legend name
'Number existing'		=>	'Existing attachment #<strong>%s</strong>',

//edit.php
'Existing'				=>	'Existing attachments: ',	//Used in edit.php, before the existing attachments that you're allowed to delete

// attach.php
'Download:'				=>	'Download:',

//rules
'Group attach part'		=>	'Attachment extension group permissions',
'Rules'					=>	'Attachment rules',
'Download'				=>	'Allow dowload',
'Upload'				=>	'Allow upload',
'Delete'				=>	'Allow delete',
'Owner delete'			=>	'Allow user to delete his own file',
'Size'					=>	'Max filesize',
'Size comment'			=>	'Max size of the uploaded file (in bytes).',
'Per post'				=>	'Attachments per post',
'Allowed files'			=>	'Allowed files',
'Allowed comment'		=>	'If empty, allow all files except those to always deny.',

// NoticesF
'Wrong post'			=>	'You have entered wrong post id. Please correct.',
'Forbid delete'			=>	'You do not have permissions to delete attachments.',
'Forbid upload'			=>	'You do not have permissions to upload files.',
'Bad type'				=>	'The file you tried to upload is not of an allowed type.',
'Too large ini'			=>	'The selected file was too large to upload. The server didn\'t allow the upload.',
'Wrong icon/name'		=>	'You have entered wrong extension/icon name',
'No icons'				=>	'You have entered empty value of extension/icon name. Please, go back and correct.',
'Wrong deny'			=>	'You have entered wrong list of denied extensions. Please, go back and correct.',
'Wrong allowed'			=>	'You have entered wrong list of allowed extensions. Please, go back and correct.',
'Big icon'				=>	'The icon <strong>%s</strong> is too wide/high. Please, select another.',
'Bad filepointer'		=>	'Error creating filepointer for file to delete/reset size, for attachment with id: %s',
'Missing icons'			=>	'The following icons are missing:',
'Big icons'				=>	'The following icons are too wide/high:',
'Size warn'				=>	'The entered value exceeds the maximally allowed upload file size for PHP. It was replaced with <strong>%s</strong>.',

'Error: mkdir'			=>	'Unable to create new subfolder with name',
'Error: 0750'			=>	'with mode 0750',
'Error: .htaccess'		=>	'Unable to copy .htaccess file to new subfolder with name',
'Error: index.html'		=>	'Unable to copy index.html file to new subfolder with name',
'Some more salt keywords'	=> 'Some more salt keywords, change if you want to',
'Put salt'				=>	'put your salt here',
'Attachment options'	=>	'Attachment options',
'Rename attachment'		=>	'Rename attachment',
'Old name'				=>	'Old name',
'New name'				=>	'New name',
'Input new attachment name'	=>	'Input new attachment name (without extension)',
'Attachments'			=>	'Attachments',
'Start at'				=>	'Start at',
'Number to show'		=>	'Number to show',
'to'					=>	'to',
'Owner'					=>	'Owner',
'Topic'					=>	'Topic',
'Order by'				=>	'Order by',
'Result sort order'		=>	'Result sort order',
'Orphans'				=>	'Orphans',
'Apply'					=>	'Apply',
'Show only "Orphans"'	=>	'Show only "Orphans"',
'Error creating attachment'	=>	'Error creating attachment, inform the owner of this bulletin board of this problem',
'Use icons'				=>	'Use icons',
'Error while deleting attachment'	=>	'Error while deleting attachment. Attachment not deleted.',
'Salt keyword'			=>	'Salt keyword, replace if you want to',

'Too short filename'	=>	'Please, enter not empty filename if you want to rename this attachment.',
'Wrong post id'			=>	'You have entered wrong post id. Please, correct it if you want to attach file to post.',
'Empty post id'			=>	'Please, enter not empty post id if you want to attach this file to post.',
'Attach error'			=>	'<strong>Warning!</strong> The following errors must be corrected before you can attach file:',
'Rename error'			=>	'<strong>Warning!</strong> The following errors must be corrected before you can rename attachment:',

'Delete'				=>	'Delete',
'Edit attachments'		=>	'Edit attachments',
'Post attachments'		=>	'Post attachments',
'Image preview'			=>	'Preview image',

'Manage attahcments'	=>	'Manage attachments',
'Manage id'				=>	'Manage attachment %s',

'Permission denied'		=>	'The directory "FORUM_ROOT/extensions/pun_attachment/attachments" is not writable for Web-server!',
'Htaccess fail'			=>	'File "FORUM_ROOT/extensions/pun_attachment/attachments/.htaccess" does not exist.',
'Index fail'			=>	'File "FORUM_ROOT/extensions/pun_attachment/attachments/index.html" does not exist.',
'Errors notice'			=>	'Following errors was encountered:',

'Del perm error'		=>	'You don\'t have permission to delete this file.',
'Up perm error'			=>	'You don\'t have permission to upload file to this post.',

'Attach limit error'	=>	'You can add only %s attachments to this post.',
'Ext error'				=>	'You can\'t add attachment with "%s" extension.',
'Filesize error'		=>	'You can\'t upload file, which size more than "%s" bytes.',
'Bad image'				=>	'Bad image! Try upload it again.',
'Add file'				=>	'Add file',
'Post attachs'			=>	'Post\'s attachments',
'Download perm error'	=>	'You don\'t have permssions to download the attachments of this post.'

);
