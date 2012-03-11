<?php

/**
 * pun_pm functions: logic, database and output
 *
 * @copyright (C) 2008-2012 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_pm
 */

if (!defined('FORUM'))
	die();

// FUNCTIONS

// enables upgrade from 0.9beta
function pun_pm_new_messages_text($s)
{
	return $s;
}

// Erases user's ($id) cache
function pun_pm_clear_cache($id)
{
	global $forum_db, $forum_user;

	$query = array(
		'UPDATE'	=> 'users',
		'SET'		=> 'pun_pm_new_messages = NULL',
		'WHERE'		=> 'id = '.$id,
	);

	($hook = get_hook('pun_pm_fn_clear_cache_pre_query')) ? eval($hook) : null;

	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	if ($forum_user['id'] == $id)
		unset($forum_user['pun_pm_new_messages']);
}

// Returns the number of unread messages ($count) and the inbox full flag ($flag) for the current user
function pun_pm_read_cache()
{
	global $forum_user;

	if (!isset($forum_user['pun_pm_new_messages']))
		return array(false, false);

	$count = $forum_user['pun_pm_new_messages'];

	if ($count == -1)
		return array(0, true);

	return array($count, false);
}

// Writes the number of unread messages ($count) and the inbox full flag ($flag) to the user's ($id) cache
function pun_pm_write_cache($id, $count, $flag)
{
	global $forum_db;

	if ($count == 0 && $flag)
		$count = - 1;

	$query = array(
		'UPDATE'	=> 'users',
		'SET'		=> 'pun_pm_new_messages = '.$count,
		'WHERE'		=> 'id = '.$id,
	);

	($hook = get_hook('pun_pm_fn_write_cache_pre_query')) ? eval($hook) : null;

	// Error handling was commented since some bugs appear while upgrading from earlier versions (when there was no cache)
	$forum_db->query_build($query);// or error(__FILE__, __LINE__);
}

// Bad global variable :(
// But it allows to avoid a DB query :)
$pun_pm_my_inbox_full = false;

function pun_pm_deliver_messages()
{
	if (defined('PUN_PM_DELIVERED_MESSAGES'))
		return;

	global $forum_db, $forum_user, $forum_config, $lang_pun_pm, $pun_pm_my_inbox_full, $pun_pm_my_inbox_count;

	if ($forum_config['o_pun_pm_inbox_size'] == 0)
	{
		// Unlimited Inbox!
		// Deliver all messages that were sent
		$query = array(
			'UPDATE'	=> 'pun_pm_messages',
			'SET'		=> 'status = \'delivered\'',
			'WHERE'		=> 'receiver_id = '.$forum_user['id'].' AND status = \'sent\'',
		);

		($hook = get_hook('pun_pm_fn_deliver_messages_unlimited_pre_query')) ? eval($hook) : null;

		$forum_db->query_build($query) or error(__FILE__, __LINE__);
	}
	else
	{
		// How many messages does user have in the Inbox?
		$inbox_count = pun_pm_inbox_count($forum_user['id']);

		if ($inbox_count < $forum_config['o_pun_pm_inbox_size'])
		{
			// What messages will we deliver?
			$query = array(
				'SELECT'	=> 'id',
				'FROM'		=> 'pun_pm_messages',
				'WHERE'		=> 'receiver_id = '.$forum_user['id'].' AND status = \'sent\'',
				'ORDER BY'	=> 'lastedited_at',
				'LIMIT'		=> (string)($forum_config['o_pun_pm_inbox_size'] - $inbox_count),
			);

			($hook = get_hook('pun_pm_fn_deliver_messages_limited_pre_fetch_ids_query')) ? eval($hook) : null;

			$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

			$ids = array();
			while ($row = $forum_db->fetch_assoc($result))
				$ids[] = intval($row['id']);

			// We have to deliver some messages
			if (!empty($ids))
			{
				// There is some free space in the Inbox
				// Deliver some messages that were sent
				$query = array(
					'UPDATE'	=> 'pun_pm_messages',
					'SET'		=> 'status = \'delivered\'',
					'WHERE'		=> 'id IN ('.implode(',', $ids).')',
				);

				($hook = get_hook('pun_pm_fn_deliver_messages_limited_pre_deliver_query')) ? eval($hook) : null;

				$forum_db->query_build($query) or error(__FILE__, __LINE__);

				// Clear cached inbox count
				$pun_pm_my_inbox_count = false;
			}
		}
		else
			$pun_pm_my_inbox_full = true;
	}

	define('PUN_PM_DELIVERED_MESSAGES', 1);

	($hook = get_hook('pun_pm_fn_deliver_messages_end')) ? eval($hook) : null;
}

// Returns text for 'New messages' link
function pun_pm_unread_messages()
{
	global $forum_db, $forum_user, $forum_config, $lang_pun_pm, $pun_pm_my_inbox_full;

	list($new_messages, $pun_pm_my_inbox_full) = pun_pm_read_cache();

	($hook = get_hook('pun_pm_fn_unread_messages_after_read_cache')) ? eval($hook) : null;

	if ($new_messages === false)
	{
		($hook = get_hook('pun_pm_fn_unread_messages_pre_deliver_messages')) ? eval($hook) : null;

		pun_pm_deliver_messages();

		//How many delivered messages do we have?
		$query = array(
			'SELECT'	=> 'count(id)',
			'FROM'		=> 'pun_pm_messages',
			'WHERE'		=> 'receiver_id = '.$forum_user['id'].' AND status = \'delivered\' AND deleted_by_receiver = 0'
		);

		($hook = get_hook('pun_pm_fn_unread_messages_pre_query')) ? eval($hook) : null;

		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		list($new_messages) = $forum_db->fetch_row($result);

		($hook = get_hook('pun_pm_fn_unread_messages_pre_write_cache')) ? eval($hook) : null;

		pun_pm_write_cache($forum_user['id'], $new_messages, $pun_pm_my_inbox_full);
	}

	$return = $new_messages ? '<strong>'.$lang_pun_pm['New link'].'</strong>' : (!$pun_pm_my_inbox_full ? $lang_pun_pm['New link'] : $lang_pun_pm['New link full']);

	($hook = get_hook('pun_pm_fn_unread_messages_end')) ? eval($hook) : null;

	return $return;
}

// Returns 'NULL' for an empty username or errors for an incorrect username
function pun_pm_get_receiver_id($username, &$errors)
{
	global $lang_pun_pm, $forum_db, $forum_user;

	$receiver_id = 'NULL';

	if ($username != '')
	{
		$query = array(
			'SELECT'	=> 'id',
			'FROM'		=> 'users',
			'WHERE'		=> 'username=\''.$forum_db->escape($username).'\''
		);

		($hook = get_hook('pun_pm_fn_get_receiver_id_pre_query')) ? eval($hook) : null;

		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		$row = $forum_db->fetch_assoc($result);
		if (!$row)
			$errors[] = sprintf($lang_pun_pm['Non-existent username'], forum_htmlencode($username));
		else
			$receiver_id = intval($row['id']);

		if ($forum_user['id'] == $receiver_id)
			$errors[] = $lang_pun_pm['Message to yourself'];
	}

	($hook = get_hook('pun_pm_fn_get_receiver_id_end')) ? eval($hook) : null;

	return $receiver_id;
}

function pun_pm_get_username($id)
{
	global $forum_db;

	$query = array(
		'SELECT'	=> 'username',
		'FROM'		=> 'users',
		'WHERE'		=> 'id='.intval($id),
	);

	($hook = get_hook('pun_pm_fn_get_username_pre_query')) ? eval($hook) : null;

	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);


	$row = $forum_db->fetch_assoc($result);
	if ($row)
		$username = $row['username'];
	else
		$username = '';

	($hook = get_hook('pun_pm_fn_get_username_end')) ? eval($hook) : null;

	return $username;
}

$pun_pm_my_inbox_count = false;

function pun_pm_inbox_count($userid)
{
	global $forum_db, $forum_user, $pun_pm_my_inbox_count;

	if ($forum_user['id'] == $userid && $pun_pm_my_inbox_count !== false)
		return $pun_pm_my_inbox_count;

	$query = array(
		'SELECT'	=> 'count(id)',
		'FROM'		=> 'pun_pm_messages',
		'WHERE'		=> 'receiver_id = '.$forum_db->escape($userid).' AND (status = \'read\' OR status = \'delivered\') AND deleted_by_receiver = 0'
	);

	($hook = get_hook('pun_pm_fn_inbox_count_pre_query')) ? eval($hook) : null;

	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	list($count) = $forum_db->fetch_row($result);

	if ($forum_user['id'] == $userid)
		$pun_pm_my_inbox_count = $count;

	($hook = get_hook('pun_pm_fn_inbox_count_end')) ? eval($hook) : null;

	return $count;
}

function pun_pm_outbox_count($userid)
{
	global $forum_db;

	$query = array(
		'SELECT'	=> 'count(id)',
		'FROM'		=> 'pun_pm_messages',
		'WHERE'		=> 'sender_id = '.$forum_db->escape($userid).' AND deleted_by_sender = 0'
	);

	($hook = get_hook('pun_pm_fn_outbox_count_pre_query')) ? eval($hook) : null;

	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	list($count) = $forum_db->fetch_row($result);

	($hook = get_hook('pun_pm_fn_outbox_count_end')) ? eval($hook) : null;

	return $count;
}

function pun_pm_inbox_enough_space($userid, $ratio = 1, $count = false)
{
	global $forum_config;

	if ($forum_config['o_pun_pm_inbox_size'] == 0)
		return true;

	if ($count === false)
		$count = pun_pm_inbox_count($userid);

	return ($count < $ratio * $forum_config['o_pun_pm_inbox_size']);
}

function pun_pm_outbox_enough_space($userid, $ratio = 1, $count = false)
{
	global $forum_config;

	if ($forum_config['o_pun_pm_outbox_size'] == 0)
		return true;

	if ($count === false)
		$count = pun_pm_outbox_count($userid);

	return ($count < $ratio * $forum_config['o_pun_pm_outbox_size']);
}

// ACTIONS

function pun_pm_send_message($body, $subject, $receiver_username, &$message_id)
{
	global $lang_pun_pm, $forum_user, $forum_db, $forum_url, $forum_config, $forum_flash;

	if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== generate_form_token(forum_link($forum_url['pun_pm_send'])))
		csrf_confirm_form();

	$errors = array();

	($hook = get_hook('pun_pm_fn_send_message_pre_validation')) ? eval($hook) : null;

	$receiver_id = pun_pm_get_receiver_id($receiver_username, $errors);
	if ($receiver_id == 'NULL' && empty($errors))
		$errors[] = $lang_pun_pm['Empty receiver'];

	// Clean up body from POST
	$body = forum_linebreaks($body);

	if ($body == '')
		$errors[] = $lang_pun_pm['Empty body'];
	elseif (strlen($body) > FORUM_MAX_POSTSIZE_BYTES)
		$errors[] = sprintf($lang_pun_pm['Too long message'], forum_number_format(strlen($body)), forum_number_format(FORUM_MAX_POSTSIZE_BYTES));
	elseif ($forum_config['p_message_all_caps'] == '0' && utf8_strtoupper($body) == $body && !$forum_page['is_admmod'])
		$body = utf8_ucwords(utf8_strtolower($body));

	// Validate BBCode syntax
	if ($forum_config['p_message_bbcode'] == '1' || $forum_config['o_make_links'] == '1')
	{
		global $smilies;
		if (!defined('FORUM_PARSER_LOADED'))
			require FORUM_ROOT.'include/parser.php';
		$body = preparse_bbcode($body, $errors);
	}

	($hook = get_hook('pun_pm_fn_send_message_pre_errors_check')) ? eval($hook) : null;

	if (count($errors))
		return $errors;

	$now = time();

	if ($message_id !== false)
	{
		// Draft -> Sent
		$query = array(
			'UPDATE'		=> 'pun_pm_messages',
			'SET'			=> 'status = \'sent\', receiver_id = '.$receiver_id.', lastedited_at = '.$now.', subject = \''.$forum_db->escape($subject).'\', body=\''.$forum_db->escape($body).'\'',
			'WHERE'			=> 'id = '.$message_id.' AND sender_id = '.$forum_user['id'].' AND (status = \'draft\' OR status = \'sent\')'
		);

		($hook = get_hook('pun_pm_fn_send_message_pre_draft_send_query')) ? eval($hook) : null;

		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		if ($forum_db->affected_rows() != 1)
		{
			$message_id = false;
			$errors[] = $lang_pun_pm['Invalid message send'];
			return $errors;
		}
	}
	else
	{
		// Send new message

		// Verify outbox count
		if (!pun_pm_outbox_enough_space($forum_user['id']))
		{
			$errors[] = sprintf($lang_pun_pm['Outbox full'], $forum_config['o_pun_pm_outbox_size']);
			return $errors;
		}

		// Save to DB
		$query = array(
			'INSERT'		=> 'sender_id, receiver_id, status, lastedited_at, read_at, subject, body',
			'INTO'			=> 'pun_pm_messages',
			'VALUES'		=> $forum_user['id'].', '.$receiver_id.', \'sent\', '.$now.', 0, \''.$forum_db->escape($subject).'\', \''.$forum_db->escape($body).'\''
		);

		($hook = get_hook('pun_pm_fn_send_message_pre_new_send_query')) ? eval($hook) : null;

		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	}

	pun_pm_clear_cache($receiver_id); // Clear cached 'New messages' in the user table

	$forum_flash->add_info($lang_pun_pm['Message sent']);

	($hook = get_hook('pun_pm_fn_send_message_pre_redirect')) ? eval($hook) : null;

	redirect(forum_link($forum_url['pun_pm_outbox']), $lang_pun_pm['Message sent']);
}

function pun_pm_save_message($body, $subject, $receiver_username, &$message_id)
{
	global $lang_pun_pm, $forum_user, $forum_db, $forum_url, $forum_config, $forum_flash;

	if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== generate_form_token(forum_link($forum_url['pun_pm_send'])))
		csrf_confirm_form();

	$errors = array();

	($hook = get_hook('pun_pm_fn_save_message_pre_validation')) ? eval($hook) : null;

	$receiver_id = pun_pm_get_receiver_id($receiver_username, $errors);

	// Clean up body from POST
	$body = forum_linebreaks($body);

	if (strlen($body) > FORUM_MAX_POSTSIZE_BYTES)
		$errors[] = sprintf($lang_pun_pm['Too long message'], forum_number_format(strlen($body)), forum_number_format(FORUM_MAX_POSTSIZE_BYTES));
	else if ($forum_config['p_message_all_caps'] == '0' && utf8_strtoupper($body) == $body && !$forum_page['is_admmod'])
		$body = utf8_ucwords(utf8_strtolower($body));

	// Validate BBCode syntax
	if ($forum_config['p_message_bbcode'] == '1' || $forum_config['o_make_links'] == '1')
	{
		global $smilies;
		if (!defined('FORUM_PARSER_LOADED'))
			require FORUM_ROOT.'include/parser.php';
		$body = preparse_bbcode($body, $errors);
	}

	// Verify for errors
	if ($body == '' && $subject == '' && $receiver_username == '')
		$errors[] = $lang_pun_pm['Empty all fields'];

	($hook = get_hook('pun_pm_fn_save_message_pre_errors_check')) ? eval($hook) : null;

	if (count($errors))
		return $errors;

	$now = time();

	if ($message_id !== false)
	{
		// Edit message

		$query = array(
			'UPDATE'		=> 'pun_pm_messages',
			'SET'			=> 'status = \'draft\', receiver_id = '.$receiver_id.', lastedited_at = '.$now.', subject = \''.$forum_db->escape($subject).'\', body=\''.$forum_db->escape($body).'\'',
			'WHERE'			=> 'id = '.$message_id.' AND sender_id = '.$forum_user['id'].' AND (status = \'draft\' OR status = \'sent\')'
		);

		($hook = get_hook('pun_pm_fn_save_message_pre_edit_query')) ? eval($hook) : null;

		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		if ($forum_db->affected_rows() != 1)
		{
			$message_id = false;
			$errors[] = $lang_pun_pm['Invalid message save'];
			return $errors;
		}
	}
	else
	{
		// Save new message

		// Verify outbox count
		if (!pun_pm_outbox_enough_space($forum_user['id']))
		{
			$errors[] = sprintf($lang_pun_pm['Outbox full'], $forum_config['o_pun_pm_outbox_size']);
			return $errors;
		}

		// Save to DB
		$query = array(
			'INSERT'		=> 'sender_id, receiver_id, lastedited_at, read_at, status, subject, body',
			'INTO'			=> 'pun_pm_messages',
			'VALUES'		=> $forum_user['id'].', '.$receiver_id.', '.$now.', 0, \'draft\', \''.$forum_db->escape($subject).'\', \''.$forum_db->escape($body).'\''
		);

		($hook = get_hook('pun_pm_fn_save_message_pre_new_save_query')) ? eval($hook) : null;

		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	}

	$forum_flash->add_info($lang_pun_pm['Message saved']);

	($hook = get_hook('pun_pm_fn_save_message_pre_redirect')) ? eval($hook) : null;

	redirect(forum_link($forum_url['pun_pm_outbox']), $lang_pun_pm['Message saved']);
}

function pun_pm_edit_message()
{
	global $forum_db, $forum_user, $lang_pun_pm;

	$pun_pm_message_id = (int) $_GET['message_id'];

	$errors = array();

	// Verify input data
	$query = array(
		'SELECT'	=> 'm.id as id, m.sender_id as sender_id, m.status as status, u.username as username, m.subject as subject, m.body as body',
		'FROM'		=> 'pun_pm_messages m',
		'JOINS'		=> array(
			array(
				'LEFT JOIN'		=> 'users AS u',
				'ON'			=> '(u.id = m.receiver_id)'
			),
		),
		'WHERE'		=> 'm.id = '.$pun_pm_message_id.' AND m.sender_id = '.$forum_user['id'].' AND m.deleted_by_sender = 0'
	);

	($hook = get_hook('pun_pm_fn_edit_message_pre_validate_query')) ? eval($hook) : null;

	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$row = $forum_db->fetch_assoc($result);
	if ($row)
	{
		if ($row['status'] == 'sent')
		{
			$now = time();

			// Change status to 'draft'
			$query = array(
				'UPDATE'		=> 'pun_pm_messages',
				'SET'			=> 'status = \'draft\', lastedited_at = '.$now,
				'WHERE'			=> 'id = '.$pun_pm_message_id.' AND (status = \'draft\' OR status = \'sent\')'
			);

			($hook = get_hook('pun_pm_fn_edit_message_pre_status_change_query')) ? eval($hook) : null;

			$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

			// An error occured
			if ($forum_db->affected_rows() == 0)
				$errors[] = $lang_pun_pm['Delivered message'];
		}
		elseif ($row['status'] != 'draft')
			$errors[] = $lang_pun_pm['Delivered message'];
	}
	else
		$errors[] = $lang_pun_pm['Non-existent message'];

	($hook = get_hook('pun_pm_fn_edit_message_pre_errors_check')) ? eval($hook) : null;

	// An error occured. Go displaying error message
	if (count($errors))
		return pun_pm_edit_message_errors($errors);

	$notice = $row['status'] == 'sent' ? "\t\t\t".'<div class="ct-box info-box">'."\n\t\t\t\t".'<p class="important">'.$lang_pun_pm['Sent -> draft'].'</p>'."\n\t\t\t".'</div>'."\n" : false;
	$preview = $row['status'] == 'draft' ? pun_pm_preview($row['username'], $row['subject'], $row['body'], $errors) : false;

	($hook = get_hook('pun_pm_fn_edit_message_end')) ? eval($hook) : null;

	return pun_pm_send_form($row['username'], $row['subject'], $row['body'], $row['id'], false, $notice, $preview);
}

function pun_pm_preview($receiver, $subject, $body, &$errors)
{
	global $forum_config, $forum_page, $lang_pun_pm, $forum_user;

	if ($body == '')
		$errors[] = $lang_pun_pm['Empty body'];
	elseif (strlen($body) > FORUM_MAX_POSTSIZE_BYTES)
		$errors[] = sprintf($lang_pun_pm['Too long message'], forum_number_format(strlen($body)), forum_number_format(FORUM_MAX_POSTSIZE_BYTES));
	elseif ($forum_config['p_message_all_caps'] == '0' && utf8_strtoupper($body) == $body && !$forum_page['is_admmod'])
		$body = utf8_ucwords(utf8_strtolower($body));

	// Validate BBCode syntax
	if ($forum_config['p_message_bbcode'] == '1' || $forum_config['o_make_links'] == '1')
	{
		global $smilies;
		if (!defined('FORUM_PARSER_LOADED'))
			require FORUM_ROOT.'include/parser.php';
		$body = preparse_bbcode($body, $errors);
	}

	($hook = get_hook('pun_pm_fn_preview_pre_errors_check')) ? eval($hook) : null;

	if (count($errors))
		return false;

	$message['sender'] = $forum_user['username'];
	$message['sender_id'] = $forum_user['id'];
	$message['body'] = $body;
	$message['subject'] = $subject;
	$message['status'] = 'draft';
	$message['sent_at'] = time();

	($hook = get_hook('pun_pm_fn_preview_end')) ? eval($hook) : null;

	return pun_pm_message($message, 'inbox');
}

// PAGES

function pun_pm_next_reply($str)
{
	if (substr($str, 0, 4) == 'Re: ')
		return 'Re[2]: ' . substr($str, 4);
	$str1 = preg_replace('#^Re\[(\d{1,10})\]: #eu', '\'Re[\'.(\\1 + 1).\']: \'', $str);
	return $str == $str1 ? 'Re: ' . $str : $str1;
}

function pun_pm_inbox()
{
	global $forum_config, $lang_profile, $forum_url, $lang_common, $lang_pun_pm, $forum_user, $forum_db;

	pun_pm_deliver_messages();

	// How many messages do we have?
	$page['count'] = pun_pm_inbox_count($forum_user['id']);
	if (!pun_pm_inbox_enough_space($forum_user['id'], 1, $page['count']))
		$page['full_box'] = sprintf($lang_pun_pm['Inbox overflow'], $forum_config['o_pun_pm_inbox_size']);
	elseif (!pun_pm_inbox_enough_space($forum_user['id'], 0.75, $page['count']))
		$page['full_box'] = sprintf($lang_pun_pm['Inbox almost full'], $forum_config['o_pun_pm_inbox_size']);

	// Determine the topic offset (based on $_GET['p'])
	$page['num_pages'] = ceil($page['count'] / $forum_user['disp_topics']);
	$page['page'] = (!isset($_GET['p']) || !is_numeric($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $page['num_pages']) ? 1 : $_GET['p'];
	$page['start_from'] = $forum_user['disp_topics'] * ($page['page'] - 1);
	$page['finish_at'] = min(($page['start_from'] + $forum_user['disp_topics']), ($page['count']));

	// Setup the form
	$page['type'] = 'inbox';
	$page['heading'] = $lang_pun_pm['Inbox'];
	$page['user_role'] = $lang_pun_pm['Sender'];

	$query = array(
		'SELECT'	=> 'm.id as id, status, sender_id as user_id, subject, body, lastedited_at as sent_at, u.username as username',
		'FROM'		=> 'pun_pm_messages m',
		'JOINS'		=> array(
			array(
				'LEFT JOIN'		=> 'users u',
				'ON'			=> '(u.id=sender_id)'
			),
		),
		'WHERE'		=> 'receiver_id = '.$forum_user['id'].' AND deleted_by_receiver = 0 AND (status = \'delivered\' OR status = \'read\')',
		'ORDER BY'	=> 'lastedited_at DESC',
		'LIMIT'		=> $page['start_from'].', '.$forum_user['disp_topics']
	);

	($hook = get_hook('pun_pm_fn_inbox_pre_query')) ? eval($hook) : null;

	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$messages = array();
	while ($row = $forum_db->fetch_assoc($result))
		$messages[] = $row;

	$page['list'] = $messages;

	($hook = get_hook('pun_pm_fn_inbox_end')) ? eval($hook) : null;

	return pun_pm_box($page);
}

function pun_pm_outbox()
{
	global $forum_config, $forum_url, $lang_common, $lang_pun_pm, $forum_user, $forum_db;

	// How much messages do we have?
	$page['count'] = pun_pm_outbox_count($forum_user['id']);
	if (!pun_pm_outbox_enough_space($forum_user['id'], 0.75, $page['count']))
		$page['full_box'] = sprintf($lang_pun_pm['Outbox almost full'], $forum_config['o_pun_pm_outbox_size']);

	// Determine the topic offset (based on $_GET['p'])
	$page['num_pages'] = ceil($page['count'] / $forum_user['disp_topics']);
	$page['page'] = (!isset($_GET['p']) || !is_numeric($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $page['num_pages']) ? 1 : $_GET['p'];
	$page['start_from'] = $forum_user['disp_topics'] * ($page['page'] - 1);
	$page['finish_at'] = min(($page['start_from'] + $forum_user['disp_topics']), ($page['count']));

	// Setup the form
	$page['type'] = 'outbox';
	$page['heading'] = $lang_pun_pm['Outbox'];
	$page['user_role'] = $lang_pun_pm['Receiver'];

	$query = array(
		'SELECT'	=> 'm.id as id, status, receiver_id as user_id, subject, body, lastedited_at as sent_at, username',
		'FROM'		=> 'pun_pm_messages m',
		'JOINS'		=> array(
			array(
				'LEFT JOIN'		=> 'users u',
				'ON'			=> '(u.id=receiver_id)'
			),
		),
		'WHERE'		=> 'sender_id='.$forum_user['id'].' AND deleted_by_sender=0',
		'ORDER BY'	=> 'lastedited_at DESC',
		'LIMIT'		=> $page['start_from'].', '.$forum_user['disp_topics']
	);

	($hook = get_hook('pun_pm_fn_outbox_pre_query')) ? eval($hook) : null;

	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$messages = array();
	while ($row = $forum_db->fetch_assoc($result))
		$messages[] = $row;

	$page['list'] = $messages;

	($hook = get_hook('pun_pm_fn_outbox_end')) ? eval($hook) : null;

	return pun_pm_box($page);
}

function pun_pm_get_message($id, $type)
{
	global $forum_db, $forum_user;

	if ($type == 'inbox')
		$condition = 'm.receiver_id = '.$forum_user['id'];
	elseif ($type == 'outbox')
		$condition = 'm.sender_id = '.$forum_user['id'];
	else
		return false;

	// Obtain message
	$query = array(
		'SELECT'	=> 'm.id as id, sender_id, receiver_id, m.status, u1.username AS sender, u2.username AS receiver, read_at as read_at, lastedited_at as sent_at, subject, body',
		'FROM'		=> 'pun_pm_messages m',
		'JOINS'		=> array(
			array(
				'LEFT JOIN'		=> 'users AS u1',
				'ON'			=> '(u1.id=sender_id)'
			),
			array(
				'LEFT JOIN'		=> 'users AS u2',
				'ON'			=> '(u2.id=receiver_id)'
			),
		),
		'WHERE'		=> 'm.id='.$forum_db->escape($id).' AND '.$condition,
	);

	($hook = get_hook('pun_pm_fn_get_message_pre_query')) ? eval($hook) : null;

	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$message = $forum_db->fetch_assoc($result);
	if (!$message)
		return false;

	// Update the status of an read message
	if ($type == 'inbox' && $message['status'] == 'delivered')
	{
		$now = time();

		$query = array(
			'UPDATE'	=> 'pun_pm_messages',
			'SET'		=> 'status = \'read\', read_at = '.$now,
			'WHERE'		=> 'id='.$id,
		);

		($hook = get_hook('pun_pm_fn_get_message_pre_update_status_query')) ? eval($hook) : null;

		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		pun_pm_clear_cache($message['receiver_id']);
	}

	($hook = get_hook('pun_pm_fn_get_message_end')) ? eval($hook) : null;

	return $message;
}

function pun_pm_delete_from_inbox ($ids)
{
	global $forum_db, $forum_user;

	// Typecast to avoid a hacker attack
	foreach ($ids as $key => $id)
		$ids[$key] = (int) $id;

	$query = array(
		'DELETE'	=> 'pun_pm_messages',
		'WHERE'		=> 'id in ('.$forum_db->escape(implode(', ', $ids)).') AND receiver_id = '.$forum_user['id'].' AND deleted_by_sender = 1',
	);

	($hook = get_hook('pun_pm_fn_delete_from_inbox_delete_query')) ? eval($hook) : null;

	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$query = array(
		'UPDATE'	=> 'pun_pm_messages',
		'SET'		=> 'deleted_by_receiver = 1',
		'WHERE'		=> 'id in ('.$forum_db->escape(implode(', ', $ids)).') AND receiver_id = '.$forum_user['id'],
	);

	($hook = get_hook('pun_pm_fn_delete_from_inbox_mark_deleted_query')) ? eval($hook) : null;

	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	pun_pm_deliver_messages();
	pun_pm_clear_cache($forum_user['id']);

	($hook = get_hook('pun_pm_fn_delete_from_inbox_end')) ? eval($hook) : null;
}

function pun_pm_delete_from_outbox ($ids)
{
	global $forum_db, $forum_user;

	// Typecast to avoid a hacker attack
	foreach ($ids as $key => $id)
		$ids[$key] = (int) $id;

	$query = array(
		'DELETE'	=> 'pun_pm_messages',
		'WHERE'		=> 'id in ('.implode(', ', $ids).') AND sender_id = '.$forum_user['id'].' AND (status = \'draft\' OR status = \'sent\' OR deleted_by_receiver = 1)',
	);

	($hook = get_hook('pun_pm_fn_delete_from_outbox_delete_query')) ? eval($hook) : null;

	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$query = array(
		'UPDATE'	=> 'pun_pm_messages',
		'SET'		=> 'deleted_by_sender = 1',
		'WHERE'		=> 'id in ('.implode(', ', $ids).') AND sender_id = '.$forum_user['id'],
	);

	($hook = get_hook('pun_pm_fn_delete_from_outbox_mark_deleted_query')) ? eval($hook) : null;

	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	($hook = get_hook('pun_pm_fn_delete_from_outbox_end')) ? eval($hook) : null;
}

function pun_pm_delete_message($ids)
{
	global $forum_user, $forum_url, $lang_pun_pm, $forum_flash;

	if (isset($_POST['pm_delete_inbox']))
	{
		pun_pm_delete_from_inbox($ids);

		$forum_flash->add_info($lang_pun_pm['Message deleted']);

		($hook = get_hook('pun_pm_fn_delete_message_inbox_pre_redirect')) ? eval($hook) : null;

		redirect(forum_link($forum_url['pun_pm_inbox']), $lang_pun_pm['Message deleted']);
	}
	elseif (isset($_POST['pm_delete_outbox']))
	{
		pun_pm_delete_from_outbox($ids);

		$forum_flash->add_info($lang_pun_pm['Message deleted']);

		($hook = get_hook('pun_pm_fn_delete_message_outbox_pre_redirect')) ? eval($hook) : null;

		redirect(forum_link($forum_url['pun_pm_outbox']), $lang_pun_pm['Message deleted']);
	}

	return false;
}

// DESIGN

function pun_pm_get_page(&$page)
{
	global $forum_url, $forum_user, $lang_common;

	$return = ($hook = get_hook('pun_pm_fn_get_page_new_page')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	if ($page == 'write')
	{
		if (isset($_GET['message_id']))
		{
			if (isset($_POST['pm_delete_inbox']) || isset($_POST['pm_delete_outbox']))
			{
				if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== generate_form_token(forum_link($forum_url['pun_pm_edit'], $_GET['message_id'])))
					csrf_confirm_form();

				return pun_pm_delete_message(array($_GET['message_id']));
			}
			else
				return pun_pm_edit_message();
		}
		if (isset($_POST['pm_delete']))
		{
			if (isset($_POST['pm_delete_inbox']) || isset($_POST['pm_delete_outbox']))
			{
				if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== generate_form_token(forum_link($forum_url['pun_pm_write'])))
					csrf_confirm_form();

				return pun_pm_delete_message($_POST['pm_delete']);
			}
		}
		return pun_pm_send_form();
	}
	elseif ($page == 'compose')
	{
		$receiver_id = isset($_GET['receiver_id']) ? (int) $_GET['receiver_id'] : 0;
		return pun_pm_send_form(pun_pm_get_username($receiver_id));
	}
	elseif ($page == 'outbox')
	{
		if (isset($_GET['message_id']))
		{
			$message = pun_pm_get_message((int) $_GET['message_id'], 'outbox');

			if ($message === false)
				message($lang_common['Bad request']);

			return pun_pm_message($message, 'outbox');
		}

		return pun_pm_outbox();
	}
	else
	{
		$page = 'inbox';
		if (isset($_GET['message_id']))
		{
			$message = pun_pm_get_message((int) $_GET['message_id'], 'inbox');

			if ($message === false)
				message($lang_common['Bad request']);

			return pun_pm_message($message, 'inbox');
		}
		return pun_pm_inbox();
	}
}

function pun_pm_box($forum_page)
{
	global $lang_pun_pm, $forum_url, $forum_user, $ext_info, $lang_common, $forum_loader;

	if (file_exists($ext_info['path'].'/css/'.$forum_user['style'].'/icons/'))
		$icons_path = $ext_info['url'].'/css/'.$forum_user['style'].'/icons';
	else
		$icons_path = $ext_info['url'].'/css/Oxygen/icons';

	$forum_page['group_count'] = $forum_page['fld_count'] = 0;
	$forum_page['form_action'] = forum_link($forum_url['pun_pm_write']);

	$forum_page['hidden_fields']['csrf_token'] = '<input type="hidden" name="csrf_token" value="'.generate_form_token($forum_page['form_action']).'" />';

	$pun_pm_delete_confirm_code = <<<EOL
	(function(global){
		function pun_pm_confirm_delete () {
			var a = document.all && !window.opera ? document.all : document.getElementsByTagName("*"); // in opera 9 document.all produces type mismatch error
			var count = 0;

			for (var i = a.length; i--; ) {
				if (a[i].tagName.toLowerCase() == 'input' && a[i].getAttribute("type") == "checkbox" && a[i].getAttribute("name") == "pm_delete[]" && a[i].checked) {
					count++;
				}
			}

			if (!count) {
				alert("{$lang_pun_pm['Not selected']}");
				return false;
			}

			return confirm('{$lang_pun_pm['Selected messages']} '+ count +'\\n{$lang_pun_pm['Delete confirmation']}');
		}

		function pun_pm_select_all (all_checked) {
			var a = document.all && !window.opera ? document.all : document.getElementsByTagName("*");

			for (var i = a.length; i--; ) {
				if (a[i].tagName.toLowerCase() == 'input' && a[i].getAttribute("type") == "checkbox" && a[i].getAttribute("name") == "pm_delete[]") {
					a[i].checked = all_checked;
					pun_pm_onchange_checkbox(a[i]);
				}
			}

			return true;
		}


		function pun_pm_onchange_checkbox(checkbox) {
			var checkbox = checkbox || this,
				tr = checkbox.parentNode.parentNode;

			if (checkbox.checked) {
				PUNBB.common.addClass(tr, 'selected');
			} else {
				PUNBB.common.removeClass(tr, 'selected');
			}
		}

		function pun_pm_init_delete_handler() {
			var del_submit = document.getElementById("pun_pm_delete_submit");
			if (del_submit) {
				del_submit.onclick = function () {
					return pun_pm_confirm_delete();
				};

				PUNBB.common.removeClass(del_submit, 'visual-hidden');
			}

			var select_all = document.getElementById("pun_pm_delete_all");
			if (select_all) {
				select_all.onclick = function () {
					return pun_pm_select_all(this.checked);
				};

				PUNBB.common.removeClass(select_all, 'visual-hidden');
			}

			//
			var all_el = document.all && !window.opera ? document.all : document.getElementsByTagName("*");

			for (var i = all_el.length; i--; ) {
				if (all_el[i].tagName.toLowerCase() == 'input' && all_el[i].getAttribute("type") == "checkbox" && all_el[i].getAttribute("name") == "pm_delete[]") {
					all_el[i].onchange = function () {
						pun_pm_onchange_checkbox(this);
					}
				}
			}
		}

		// Run on page load
		PUNBB.common.addDOMReadyEvent(pun_pm_init_delete_handler);
	})(window);
EOL;

	$forum_loader->add_js($pun_pm_delete_confirm_code, array('type' => 'inline'));

	($hook = get_hook('pun_pm_fn_box_pre_output')) ? eval($hook) : null;

	ob_start();

?>
	<div class="main-subhead">
		<h2 class="hn"><span><?php echo $forum_page['heading'] ?></span></h2>
	</div>
	<div class="main-content main-frm">
<?php
	if (!empty($forum_page['full_box']))
	{
?>
		<div class="ct-box info-box">
			<p class="warn">
				<?php echo $forum_page['full_box']?>
			</p>
		</div>
<?php
	}
?>
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo $forum_page['form_action'] ?>">
			<div class="hidden">
				<?php echo implode("\n\t\t\t\t", $forum_page['hidden_fields']), "\n" ?>
			</div>
<?php
	if (!count($forum_page['list']))
	{
?>
			<div class="ct-box info-box">
				<p class="important"><?php echo $lang_pun_pm['Empty box']?></p>
			</div>
<?php
	}
	if (count($forum_page['list']))
	{
		echo "\t\t\t\t", '<div class="ct-group"><table class="pun_pm_list">', "\n\t\t\t\t\t", '<thead><tr><th class="td1"><input id="pun_pm_delete_all" type="checkbox" name="pm_delete_all" value="" class="visual-hidden" /></th><th class="td2"><img src="', $icons_path, '/sent.png" height="16" width="16" alt="Status" title="Status"/></th><th class="td3">', $forum_page['user_role'], '</th><th class="td4">', $lang_pun_pm['Subject'], '</th><th class="td5">', $lang_pun_pm['Edit date'], '</th></tr></thead>', "\n\t\t\t\t\t", '<tbody>', "\n";

		foreach($forum_page['list'] as $message)
		{
			$message_link = forum_link($forum_url['pun_pm_'.($message['status'] == 'draft' ? 'edit' : 'view')], array($message['id'], $forum_page['type']));
			$message_info = array (
				$message['status'] != 'sent' ? '<input type="checkbox" name="pm_delete[]" value="'.$message['id'].'" />' : '',
				'<img src="'.$icons_path.'/'.($message['status'] == 'delivered' ? $forum_page['type'].'_' : '').$message['status'].'.png" height="16" width="16" alt="'.$message['status'].'" title="'.$message['status'].'" />',
				$message['username'] ? '<a href="'.forum_link($forum_url['user'], $message['user_id']).'">'.forum_htmlencode($message['username']).'</a>' : $lang_pun_pm['Empty'],
				'<span><a href="'.$message_link.'">'.trim($message['subject'] ? forum_htmlencode($message['subject']) : $lang_pun_pm['Empty']).'</a>'.($forum_user['pun_pm_long_subject'] == '1' ? ' <a class="mess" href="'.$message_link.'">'.forum_htmlencode(preg_replace('#(?:\s*(?:\[quote(?:=(&quot;|"|\'|)(?:.*?)\\1)?\](?:.*)\[\/quote\])*)((?:\S*\s*){20})(?:.*)$#su', '$2', $message['body'])).'</a>' : '').'</span>',
				format_time($message['sent_at']),
			);

			($hook = get_hook('pun_pm_fn_box_pre_row_output')) ? eval($hook) : null;

			echo "\t\t\t\t\t", '<tr', $forum_page['type'] == 'inbox' && $message['status'] == 'delivered' ? ' class="pm_new"': '', '>', "\n";
			$col_count = 0;
			foreach ($message_info as $value)
				echo "\t\t\t\t\t\t", '<td class="td', ++$col_count, '">', $value, '</td>', "\n";
			echo "\t\t\t\t\t", '</tr>', "\n";
		}

		echo "\t\t\t\t\t", '</tbody>', "\n\t\t\t\t", '</table></div>', "\n";
		echo "\t\t\t\t", '<div style="margin: 1.417em;"><input class="visual-hidden" type="submit" name="pm_delete_', $forum_page['type'], '" value="', $lang_pun_pm['Delete selected'], '" id="pun_pm_delete_submit"/></div>', "\n";
	}
	if ($forum_page['num_pages'] > 1) {
?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<p class="paging"><span class="pages"><?php echo $lang_common['Pages'].'</span> '.paginate($forum_page['num_pages'], $forum_page['page'], $forum_url['pun_pm_'.$forum_page['type']], $lang_common['Paging separator']) ?></p>
			</fieldset>
<?php
	}
?>
		</form>
	</div>
<?php

	$result = ob_get_contents();
	ob_end_clean();

	return $result;
}

function pun_pm_message($message, $type)
{
	global $forum_config, $forum_url, $lang_common, $lang_pun_pm, $forum_user;

	// Setup the form
	$forum_page['set_count'] = $forum_page['fld_count'] = 0;

	$forum_page['form_action'] = isset($message['id']) ? forum_link($forum_url['pun_pm_edit'], $message['id']) : '';
	$forum_page['hidden_fields']['csrf_token'] = '<input type="hidden" name="csrf_token" value="'.generate_form_token($forum_page['form_action']).'" />';

	if ($type == 'inbox')
	{
		$forum_page['heading'] = $lang_pun_pm['Incoming message'];
		$forum_page['user_text'] = $lang_pun_pm['Sender'];
		$forum_page['user_content'] = $message['sender'] != '' ? '<a href="'.forum_link($forum_url['user'], $message['sender_id']).'">'.forum_htmlencode($message['sender']).'</a>' : $lang_pun_pm['Empty'];
	}
	else
	{
		$forum_page['heading'] = $lang_pun_pm['Outgoing message'];
		$forum_page['user_text'] = $lang_pun_pm['Receiver'];
		$forum_page['user_content'] = $message['receiver'] != '' ? '<a href="'.forum_link($forum_url['user'], $message['receiver_id']).'">'.forum_htmlencode($message['receiver']).'</a>' : $lang_pun_pm['Empty'];
	}
	if (!isset($message['id']))
		$forum_page['heading'] = $lang_pun_pm['Preview message'];

	global $smilies;
	if (!defined('FORUM_PARSER_LOADED'))
		require FORUM_ROOT.'include/parser.php';

	($hook = get_hook('pun_pm_fn_message_pre_output')) ? eval($hook) : null;

	ob_start();

?>
	<div class="main-subhead">
		<h2 class="hn"><span><?php echo $forum_page['heading'] ?></span></h2>
	</div>
	<div class="main-content frm">
<?php
	if ($type == 'outbox' && $message['status'] == 'sent')
	{
?>
		<div class="ct-box info-box">
			<p class="warn">
				<?php echo $lang_pun_pm['Sent note']?>
			</p>
		</div>
<?php
	}
?>
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo $forum_page['form_action'] ?>">

			<div class="hidden">
				<?php echo implode("\n\t\t\t\t", $forum_page['hidden_fields'])."\n" ?>
			</div>

			<div class="pun_pm_msg">
				<table>
					<tbody>
						<tr>
							<td class="td1"><?php echo $forum_page['user_text'] ?></td>
							<td><?php echo $forum_page['user_content'] ?></td>
						</tr>
<?php
	if ($type == 'inbox')
	{
?>
						<tr>
							<td class="td1"><?php echo $lang_pun_pm['Sent'] ?></td>
							<td><?php echo $message['sent_at'] ? format_time($message['sent_at']) : $lang_pun_pm['Not sent'] ?></td>
						</tr>
<?php
	}
	else
	{
?>
						<tr>
							<td class="td1"><?php echo $lang_pun_pm['Status'] ?></td>
							<td><?php echo $lang_pun_pm[$message['status']], $message['status'] == 'read' ? ' '.format_time($message['read_at']) : '' ?></td>
						</tr>
<?php
	}
?>
						<tr>
							<td class="td1"><?php echo $lang_pun_pm['Subject'] ?></td>
							<td><?php echo $message['subject'] ? forum_htmlencode($message['subject']) : $lang_pun_pm['Empty'] ?></td>
						</tr>
<?php ($hook = get_hook('pun_pm_fn_message_pre_info_end')) ? eval($hook) : null; ?>
					</tbody>
				</table>
			</div>
			<div class="pun_pm_msg"><div class="post-entry"><div class="entry-content pun-pm-ct-box"><?php echo parse_message($message['body'], false) ?></div></div></div>
<?php
	if (isset($message['id']))
	{
?>
			<div class="frm-buttons">
<?php
		if ($type == 'outbox' && ($message['status'] == 'draft' || $message['status'] == 'sent'))
		{
?>				<span class="submit primary"><input type="submit" name="pm_edit" value="<?php echo $lang_pun_pm['Edit message']; ?>" /></span>
<?php
		}

		if ($type != 'outbox' || $message['status'] != 'sent')
		{
?>				<span class="submit primary caution"><input type="submit" name="pm_delete_<?php echo $type ?>" value="<?php echo $lang_pun_pm['Delete message'] ?>" onclick="return confirm('<?php echo $lang_pun_pm['Delete confirmation 1'] ?>');" /></span>
<?php
		}
?>
			</div>
<?php
	}
?>
		</form>
	</div>
<?php

	if (isset($message['id']) && $type == 'inbox')
	{
		// Sender maybe NULL if user deleted
		if (isset($message['sender']) && (utf8_strlen($message['sender']) > 0))
		{
			// Make quote
			if ($forum_config['p_message_bbcode'] == '1')
			{
				// If username contains a square bracket, we add "" or '' around it (so we know when it starts and ends)
				if (strpos($message['sender'], '[') !== false || strpos($message['sender'], ']') !== false)
				{
					if (strpos($message['sender'], '\'') !== false)
						$message['sender'] = '"'.$message['sender'].'"';
					else
						$message['sender'] = '\''.$message['sender'].'\'';
				}
				else
				{
					// Get the characters at the start and end of $q_poster
					$ends = utf8_substr($message['sender'], 0, 1).utf8_substr($message['sender'], -1, 1);

					// Deal with quoting "Username" or 'Username' (becomes '"Username"' or "'Username'")
					if ($ends == '\'\'')
						$message['sender'] = '"'.$message['sender'].'"';
					else if ($ends == '""')
						$message['sender'] = '\''.$message['sender'].'\'';
				}

				$quoted_message = '[quote='.$message['sender'].']'.$message['body'].'[/quote]'."\n";
			}
			else
			{
				$quoted_message = '> '.$message['sender'].' '.$lang_common['wrote'].':'."\n\n".'> '.$message['body']."\n";
			}

			echo pun_pm_send_form($message['sender'], pun_pm_next_reply($message['subject']), $quoted_message, false, true);
		}
	}

	$result = ob_get_contents();
	ob_end_clean();

	return $result;
}

function pun_pm_edit_message_errors($errors)
{
	global $lang_pun_pm;

	$forum_page['errors'] = array();
	foreach ($errors as $cur_error)
		$forum_page['errors'][] = '<li class="warn"><span>'.$cur_error.'</span></li>';

	($hook = get_hook('pun_pm_fn_edit_message_errors_pre_output')) ? eval($hook) : null;

	ob_start();

?>
	<div class="main-content main-frm">
		<div class="ct-box error-box">
			<h2 class="warn hn"><?php echo $lang_pun_pm['Messsage send errors'] ?></h2>
			<ul class="error-list">
				<?php echo implode("\n\t\t\t\t", $forum_page['errors'])."\n" ?>
			</ul>
		</div>
	</div>
<?php

	$output = ob_get_contents();
	ob_end_clean();

	($hook = get_hook('pun_pm_fn_edit_message_errors_pre_end')) ? eval($hook) : null;

	return $output;
}

function pun_pm_send_form($username = '', $subject = '', $body = '', $message_id = false, $reply_form = false, $notice = false, $preview = false)
{
	global $forum_config, $forum_url, $lang_common, $lang_pun_pm, $forum_user, $pun_pm_errors, $ext_info, $forum_head, $forum_loader;

	// need JS
	$forum_loader->add_js($ext_info['url'].'/js/pun_pm.shortcut.min.js', array('type' => 'url', 'async' => true));

	$username = forum_htmlencode($username);
	$subject = forum_htmlencode($subject);
	$body = forum_htmlencode($body);

	// Setup the form
	$forum_page['item_count'] = $forum_page['fld_count'] = 0;
	$forum_page['form_action'] = forum_link($forum_url['pun_pm_send']);

	$forum_page['hidden_fields']['csrf_token'] = '<input type="hidden" name="csrf_token" value="'.generate_form_token($forum_page['form_action']).'" />';
	$forum_page['hidden_fields']['send_action'] = '<input type="hidden" name="send_action" value="" />';
	if ($message_id !== false)
	{
		// Edit message
		$forum_page['hidden_fields']['message_id'] = '<input type="hidden" name="message_id" value="'.$message_id.'" />';
		$forum_page['heading'] = $lang_pun_pm['Edit message'];
	}
	elseif ($reply_form !== false)
	{
		$forum_page['heading'] = $lang_pun_pm['Quick reply'];
		$forum_page['hidden_fields']['pm_receiver'] = '<input type="hidden" name="pm_receiver" value="'.$username.'" />';
	}
	else
		$forum_page['heading'] = $lang_pun_pm['New message'];

	// Setup help
	$forum_page['text_options'] = array();
	if ($forum_config['p_message_bbcode'] == '1')
		$forum_page['text_options']['bbcode'] = '<span'.(empty($forum_page['text_options']) ? ' class="first-item"' : '').'><a class="exthelp" href="'.forum_link($forum_url['help'], 'bbcode').'" title="'.sprintf($lang_common['Help page'], $lang_common['BBCode']).'">'.$lang_common['BBCode'].'</a></span>';
	if ($forum_config['p_message_img_tag'] == '1')
		$forum_page['text_options']['img'] = '<span'.(empty($forum_page['text_options']) ? ' class="first-item"' : '').'><a class="exthelp" href="'.forum_link($forum_url['help'], 'img').'" title="'.sprintf($lang_common['Help page'], $lang_common['Images']).'">'.$lang_common['Images'].'</a></span>';
	if ($forum_config['o_smilies'] == '1')
		$forum_page['text_options']['smilies'] = '<span'.(empty($forum_page['text_options']) ? ' class="first-item"' : '').'><a class="exthelp" href="'.forum_link($forum_url['help'], 'smilies').'" title="'.sprintf($lang_common['Help page'], $lang_common['Smilies']).'">'.$lang_common['Smilies'].'</a></span>';

	($hook = get_hook('pun_pm_fn_send_form_pre_output')) ? eval($hook) : null;

	ob_start();

	if ($preview !== false)
		echo $preview;

?>
	<div class="main-subhead">
		<h2 class="hn"><span><?php echo $forum_page['heading'] ?></span></h2>
	</div>
	<div class="main-content main-frm">
<?php
	if (!empty($forum_page['text_options']))
		echo "\t\t".'<p class="ct-options options">'.sprintf($lang_common['You may use'], implode(' ', $forum_page['text_options'])).'</p>'."\n";

	if (!empty($pun_pm_errors))
	{
		$forum_page['errors'] = array();
		foreach ($pun_pm_errors as $cur_error)
			$forum_page['errors'][] = '<li class="warn"><span>'.$cur_error.'</span></li>';
?>
		<div class="ct-box error-box">
			<h2 class="warn hn"><?php echo $lang_pun_pm['Messsage send errors'] ?></h2>
			<ul class="error-list">
				<?php echo implode("\n\t\t\t\t", $forum_page['errors'])."\n" ?>
			</ul>
		</div>
<?php
	}
?>
		<form id="afocus" class="frm-form" name="pun_pm_sendform" method="post" accept-charset="utf-8" action="<?php echo $forum_page['form_action'] ?>">
<?php
	if ($notice !== false)
		echo $notice;
?>
			<div class="hidden">
				<?php echo implode("\n\t\t\t\t", $forum_page['hidden_fields'])."\n" ?>
			</div>
			<fieldset class="frm-group group1">
				<legend class="group-legend"><span><?php echo $forum_page['heading'] ?></span></legend>
<?php
	if ($reply_form === false)
	{
?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text required">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_pun_pm['To'] ?></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="pm_receiver" value="<?php echo $username; ?>" size="70" maxlength="255" required /></span>
					</div>
				</div>
<?php
	}
?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_pun_pm['Subject'] ?></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="pm_subject" value="<?php echo $subject; ?>" size="70" maxlength="255" /></span>
					</div>
				</div>
<?php ($hook = get_hook('pun_pm_fn_send_form_pre_textarea_output')) ? eval($hook) : null; ?>
				<div class="txt-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="txt-box textarea required">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_pun_pm['Message'] ?></span></label>
						<div class="txt-input"><span class="fld-input"><textarea id="fld<?php echo $forum_page['fld_count'] ?>" name="req_message" rows="14" cols="95" required><?php echo $body; ?></textarea></span></div>
					</div>
				</div>
<?php ($hook = get_hook('pun_pm_fn_send_form_pre_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
			<div class="frm-buttons">
<?php
	if ($message_id !== false)
	{
?>
				<div style="float: right;" class="primary caution"><input type="submit" name="pm_delete" value="<?php echo $lang_pun_pm['Delete draft'] ?>" onclick="return confirm('<?php echo $lang_pun_pm['Confirm delete draft'] ?>');" /></div>
<?php
	}
	($hook = get_hook('pun_pm_fn_send_form_pre_buttons_output')) ? eval($hook) : null;
?>
				<span class="submit primary"><input type="submit" name="pm_send" value="<?php echo $lang_pun_pm['Send button'] ?>" /></span>
				<span class="submit"><input type="submit" name="pm_preview" value="<?php echo $lang_pun_pm['Preview'] ?>" /></span>
				<span class="submit"><input type="submit" name="pm_draft" value="<?php echo $lang_pun_pm['Save draft'] ?>" /></span>
			</div>
		</form>
	</div>
<?php

	$result = ob_get_contents();
	ob_end_clean();

	($hook = get_hook('pun_pm_fn_send_form_pre_end')) ? eval($hook) : null;

	return $result;
}

define('PUN_PM_FUNCTIONS_LOADED', 1);
