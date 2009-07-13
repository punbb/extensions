<?php

/**
 * API for events registration
 *
 * @copyright (C) 2008-2009 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_admin_events
 */

if (!defined('FORUM_ROOT'))
	die();

if (!defined('FORUM_ESSENTIALS_LOADED'))
	require_once FORUM_ROOT.'include/essentials.php';

function pun_admin_event($type, $comment = '', $search_user = true)
{
	global $forum_db, $forum_user;

	$query = array(
		'INSERT'	=> 'ip, type, comment, date',
		'INTO'		=> 'pun_admin_events',
		'VALUES'	=> '\''.(empty($_SERVER['REMOTE_ADDR']) ? '0.0.0.0' : $_SERVER['REMOTE_ADDR']).'\', \''.$forum_db->escape($type).'\', \''.$forum_db->escape($comment).'\', '.time()
	);

	if (isset($forum_user) && $search_user == true)
	{
		$query['INSERT'] .= ', user_name, user_id';
		$query['VALUES'] .= ', \''.$forum_user['username'].'\', '.$forum_user['id'];
	}

	$forum_db->query_build($query) or error(__FILE__, __LINE__);
}

?>