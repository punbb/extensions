<?php

/**
 * Lang file for pun_admin_broadcast_email
 *
 * @copyright Copyright (C) 2008 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_admin_broadcast_email
 */

if (!defined('FORUM'))
	exit;

$lang_pun_admin_broadcast_email = array(
	'Email errors'	=>	'<strong>Warning!</strong> The following errors must be corrected before sending e-mails:',
	'Ext name'		=>	'Broadcast e-mail',
	'Group'			=>	'Group',
	'Group title'	=>	'Group title',
	'Members count'	=>	'Members count',
	'Tpl vars'		=>	'Template vars',
	'Tpl vars info'	=>	'Enable this option if you are planning to use <a class="exthelp" href="%s">special vars</a> in the e-mail template.',
	'Email subject'	=>	'E-mail subject',
	'Email message'	=>	'E-mail message',
	'Submit'		=>	'Submit',
	'Preview'		=>	'Preview',
	'Ext help'		=>	'Broadcast e-mail help',
	'Ext help header'=>	'The list of special template variables which you can use in e-mails.',
	'Err no groups'		=>	'No groups selected.',
	'Err guest group'	=>	'You can\'t send e-mail to guest users.',
	'Err no subject'	=>	'No e-mail subject.',
	'Err long subject'	=>	'The subject is too long',
	'Err no message'	=>	'No message',
	'Err long message'	=>	'The length of your e-mail message is %s bytes. This exceeds the %s bytes limit.',
	'Err per page'		=>	'Number of e-mails per page can\'t be empty or nevative.',
	'Task finished'		=>	'The task finished e-mails messages has been sent.',
	'Cookie fail'		=>	'Can\'t read information from cookie.',
	'Emails per cycle'	=>	'Emails per cycle',
	'Per cycle info'	=>	'The number of users the e-mail will be sent to. E.g. if you enter 100, one hundred e-mails will be sent and then the page will be refreshed. This is to prevent the script from timing out during the process.',
	'Click to continue'			=>	'Click here to continue',
	'Javascript redirect'		=>	'JavaScript redirect unsuccessful.',
	'Table summary'		=>	'Groups',
	'Help username'		=>	'produces username.',
	'Help user title'	=>	'produces user title.',
	'Help realname'		=>	'produces realname.',
	'Help num posts'	=>	'produces number of user posts.',
	'Help last post'	=>	'produces date of the last post of the user.',
	'Help reg date'		=>	'produces registration date of the user.',
	'Help reg IP'		=>	'produces the IP which was used by the user for registration.',
	'Help last visit'	=>	'produces date of the last visit of the user.',
	'Help admin note'	=>	'produces admin note for the user.',
	'Help user profile'		=>	'produces url to the user profile.',
);

?>