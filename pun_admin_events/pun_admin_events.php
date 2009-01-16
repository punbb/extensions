<?php

if (!defined('FORUM_ROOT'))
	die();

require_once FORUM_ROOT.'include/common.php';

function pun_admin_event($type, $comment = '', $search_user = true)
{
	global $forum_db, $forum_user;	
	$query = array(
		'INSERT'	=> 'ip, type, comment, date',
		'INTO'		=> 'pun_admin_events',
		'VALUES'	=> '\''.empty($_SERVER['REMOTE_ADDR']) ? '0.0.0.0' : $_SERVER['REMOTE_ADDR']).'\', \''.$type.'\', \''.$comment.'\', FROM_UNIXTIME('.time().')'
	);

	if(isset($forum_user) && $search_user == true)
	{
		$query['INSERT'] .= ', user_name, user_id';
		$query['VALUES'] .= ', \''.$forum_user['username'].'\', '.$forum_user['id'];
	}

	$forum_db->query_build($query) or error(__FILE__, __LINE__);
}

?>