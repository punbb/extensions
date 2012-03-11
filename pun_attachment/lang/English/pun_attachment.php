<?php

/**
 * Language file for pun_attacnment extension
 *
 * @copyright (C) 2008-2012 PunBB, partially based on Attachment Mod by Frank Hagstrom
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_attachment
 */

if (!defined('FORUM')) die();

// Language definitions for frequently used strings
$lang_attach = array(
//admin
'Display images'		=>	'Display images',
'Display small'			=>	'Images will be displayed on the viewtopic/edit page, whose size is smaller than the parameters below.',
'Disable attachments'	=>	'Disable attachments',
'Display icons' 		=>	'Enable displaying icons',
'Create orphans'		=>	'Enable it if you want to create orphans.',
'Always deny'			=>	'Always deny',
'Filesize'				=>	'File size',
'Filename'				=>	'File name',
'Max filesize'			=>	'Max file size',
'Max height'			=>	'Max height',
'Max width'				=>	'Max width',
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

'Create orphans'		=>	'Create orphans',
'Orphans help'			=>	'If this option is enabled, attachments will not be removed from the database when a user wants to delete a post with these attachments.',
'Icons help'			=>	'Icons for attachment types are stored in FORUM_ROOT/extensions/attachment/img/. To add or change icons, use the following form. In the first coloumn enter the types, entering icon names into opposing cells. PunBB allows you to use icons in png, gif, jpeg, ico formats.',


// la
'Attachment'			=>	'Attachments',
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

'Number existing'		=>	'Existing attachment #<strong>%s</strong>',

//edit.php
'Existing'				=>	'Existing attachments: ',	//Used in edit.php, before the existing attachments that you're allowed to delete

//attach.php
'Download:'				=>	'Download:',
'Attachment added'		=>	'Attachment added. Redirecting...',
'Attachment delete'		=>	'Attachment deleted. Redirecting...',

//rules
'Group attach part'		=>	'Attachments permissions',
'Rules'					=>	'Attachment rules',
'Download'				=>	'Allow users to download files',
'Upload'				=>	'Allow users to upload files',
'Delete'				=>	'Allow users to delete files',
'Owner delete'			=>	'Allow users to delete their own files',
'Size'					=>	'Max file size',
'Size comment'			=>	'Max size of the uploaded file (in bytes).',
'Per post'				=>	'Attachments per post',
'Allowed files'			=>	'Allowed files',
'Allowed comment'		=>	'If empty, allow all files except those that are always denied.',
'File len err'			=>	'File name can\'t be longer than 255 chars',
'Ext len err'			=>	'File extension can\'t be longer than 64 chars.',

// Notices
'Wrong post'			=>	'You have entered a wrong post id. Please correct it.',
'Too large ini'			=>	'The selected file was too large to upload. The server forbade the upload.',
'Wrong icon/name'		=>	'You have entered a wrong extension/icon name',
'No icons'				=>	'You have entered an empty value of extension/icon name. Please, go back and correct it.',
'Wrong deny'			=>	'You have entered a wrong list of denied extensions. Please, go back and correct it.',
'Wrong allowed'			=>	'You have entered a wrong list of allowed extensions. Please, go back and correct it.',
'Big icon'				=>	'The icon <strong>%s</strong> is too wide/high. Please, select another one.',
'Missing icons'			=>	'The following icons are missing:',
'Big icons'				=>	'The following icons are too wide/high:',

'Error: mkdir'			=>	'Unable to create new the subfolder with the name',
'Error: 0750'			=>	'with mode 0750',
'Error: .htaccess'		=>	'Unable to copy .htaccess file to the new subfolder with name',
'Error: index.html'		=>	'Unable to copy index.html file to the new subfolder with name',
'Some more salt keywords'	=> 'Some more salt keywords, change if you want to',
'Put salt'				=>	'put your salt here',
'Attachment options'	=>	'Attachment options',
'Rename attachment'		=>	'Rename attachment',
'Old name'				=>	'Old name',
'New name'				=>	'New name',
'Input new attachment name'	=>	'Input a new attachment name (without extension)',
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
'Error creating attachment'	=>	'Error whilecreating attachment, inform the owner of this bulletin board about this problem',
'Use icons'				=>	'Use icons',
'Error while deleting attachment'	=>	'Error while deleting attachment. Attachment is not deleted.',
'Salt keyword'			=>	'Salt keyword, replace if you want to',

'Too short filename'	=>	'Please, enter an unempty filename if you want to rename this attachment.',
'Wrong post id'			=>	'You have entered a wrong post id. Please, correct it if you want to attach a file to this post.',
'Empty post id'			=>	'Please, enter an unempty post id if you want to attach this file to the post.',
'Attach error'			=>	'<strong>Warning!</strong> The following errors must be corrected before you can attach a file:',
'Rename error'			=>	'<strong>Warning!</strong> The following errors must be corrected before you can rename the attachment:',

'Edit attachments'		=>	'Edit attachments',
'Post attachments'		=>	'Post attachments',
'Image preview'			=>	'Image preview',

'Manage attahcments'	=>	'Manage attachments',
'Manage id'				=>	'Manage attachment %s',

'Permission denied'		=>	'The directory "FORUM_ROOT/extensions/pun_attachment/attachments" is not writable for a Web server!',
'Htaccess fail'			=>	'File "FORUM_ROOT/extensions/pun_attachment/attachments/.htaccess" does not exist.',
'Index fail'			=>	'File "FORUM_ROOT/extensions/pun_attachment/attachments/index.html" does not exist.',
'Errors notice'			=>	'Following errors have been encountered:',

'Del perm error'		=>	'You don\'t have the permission to delete this file.',
'Up perm error'			=>	'You don\'t have the permission to upload a file to this post.',

'Attach limit error'	=>	'You can add only %s attachments to this post.',
'Ext error'				=>	'You can\'t add an attachment with "%s" extension.',
'Filesize error'		=>	'You can\'t upload a file whose size is more than "%s" bytes.',
'Bad image'				=>	'Bad image! Try uploading it again.',
'Add file'				=>	'Add file',
'Post attachs'			=>	'Post\'s attachments',
'Download perm error'	=>	'You don\'t have the permssions to download the attachments of this post.',
'None'					=>	'None',

'Id'					=>	'Id',
'Owner'					=>	'Owner',
'Up date'				=>	'Uploaded date',
'Type'					=>	'Type'

);
