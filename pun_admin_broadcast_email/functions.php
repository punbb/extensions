<?php
/**
 * pun_admin_broadcast_email functions
 *
 * @copyright (C) 2008-2009 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_admin_broadcast_email
 */
 
// Make sure no one attempts to run this script "directly"
if (!defined('FORUM'))
	exit;

define('PUN_ADMIN_BROADCAST_EMAIL_COOKIE_NAME', 'pun_admin_broadcast_email_data');

function pun_admin_broadcast_email_parse_string($subject, $user_info)
{	
	$tpl_vars = pun_admin_broadcast_email_gen_tpl_vars($user_info);
	foreach ($tpl_vars as $tpl_var => $tpl_value)
		$subject = str_ireplace($tpl_var, $tpl_value, $subject);
	return $subject;
}

function pun_admin_broadcast_email_gen_tpl_vars($user_data)
{
	global $forum_url;

	$tpl_vars = array();
	$tpl_vars['%_username_%'] = $user_data['username'];
	$tpl_vars['%_title_%'] = $user_data['title'];
	$tpl_vars['%_realname_%'] = $user_data['realname'];
	$tpl_vars['%_num_posts_%'] = $user_data['num_posts'];
	$tpl_vars['%_last_post_%'] = format_time($user_data['last_post']);
	$tpl_vars['%_registered_%'] = format_time($user_data['registered']);
	$tpl_vars['%_registration_ip_%'] = $user_data['registration_ip'];
	$tpl_vars['%_last_visit_%'] = format_time($user_data['last_visit']);
	$tpl_vars['%_admin_note_%'] = $user_data['admin_note'];
	$tpl_vars['%_profile_url_%'] = forum_link($forum_url['user'], $user_data['id']);

	return $tpl_vars;
}

function pun_admin_broadcast_email_send_mail($subject, $message, $user_data, $parse_message = TRUE)
{
	$tmp_subject = $parse_message ? pun_admin_broadcast_email_parse_string($subject, $user_data) : $subject;
	$tmp_message = $parse_message ? pun_admin_broadcast_email_parse_string($message, $user_data) : $message;

	forum_mail($user_data['email'], $tmp_subject, $tmp_message);
}

function pun_admin_broadcast_email_get_cookie_data()
{
	global $forum_db, $db_type, $forum_config, $forum_user;

	$now = time();

	// If a cookie is set, we get information about e-mails
	if (!empty($_COOKIE[PUN_ADMIN_BROADCAST_EMAIL_COOKIE_NAME]))
	{
		$cookie_data = explode('|', base64_decode($_COOKIE[PUN_ADMIN_BROADCAST_EMAIL_COOKIE_NAME]));

		if (!empty($cookie_data) && count($cookie_data) == 6)
			list($cookie['group_ids'], $cookie['use_vars'], $cookie['subject'], $cookie['message'], $cookie['expiration_time'], $cookie['expire_hash']) = $cookie_data;
		else
			return FALSE;

		if (intval($cookie['expiration_time']) <= $now)
			return FALSE;

		if ($cookie['expire_hash'] !== sha1($forum_user['salt'].$forum_user['password'].forum_hash(intval($cookie['expiration_time']), $forum_user['salt'])))
			return FALSE;

		return array('groups' => $cookie['group_ids'], 'parse_mail' => $cookie['use_vars'], 'req_subject' => $cookie['subject'], 'req_message' => $cookie['message']);
	}
	else
		return FALSE;
}

function pun_admin_broadcast_email_set_cookie_data($selected_groups, $use_tpl_vars, $email_subject, $email_message)
{
	global $forum_user, $forum_config;

	$expire = time() + $forum_config['o_timeout_online'];
	forum_setcookie(PUN_ADMIN_BROADCAST_EMAIL_COOKIE_NAME, base64_encode(implode(',', $selected_groups).'|use_vars:'.($use_tpl_vars ? '1' : '0').'|'.$email_subject.'|'.$email_message.'|'.$expire.'|'.sha1($forum_user['salt'].$forum_user['password'].forum_hash($expire, $forum_user['salt']))), $expire);
}

?>