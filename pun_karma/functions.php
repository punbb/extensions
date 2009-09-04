<?php

/**
 * pun_karma functions
 *
 * @copyright (C) 2008-2009 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_karma
 */

if (!defined('FORUM'))
	die();

function karma_plus($post_id)
{
	global $forum_db, $forum_user, $lang_pun_karma;

	//Check if user tries to vote for his own post
	$query = array(
		'SELECT'	=> '1',
		'FROM'		=> 'posts',
		'WHERE'		=> 'poster_id = '.$forum_user['id'].' AND id = '.$post_id
	);
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	if ($forum_db->num_rows($result) > 0)
		message($lang_pun_karma['Vote error']);

	//Check if user voted yet
	$query = array(
		'SELECT'	=> '1',
		'FROM'		=> 'pun_karma',
		'WHERE'		=> 'user_id = '.$forum_user['id'].' AND post_id = '.$post_id
	);
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	if (!$forum_db->num_rows($result))
	{
		$query = array(
			'INSERT'		=> 'user_id, post_id, mark, updated_at',
			'INTO'			=> 'pun_karma',
			'VALUES'		=> $forum_user['id'].', '.$post_id.', 1, '.time()
		);
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		//This query is needed to set num posts to 0 for correct karma calcualation.
		$query = array(
			'UPDATE'		=> 'posts',
			'SET'			=> 'karma = 0',
			'WHERE'			=> 'karma IS NULL AND id = '.$post_id
		);
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		$query = array(
			'UPDATE'		=> 'posts',
			'SET'			=> 'karma = karma + 1',
			'WHERE'			=> 'id = '.$post_id
		);
		$forum_db->query_build($query) or error(__FILE__, __LINE__);
	}
	else
		message($lang_pun_karma['Already voted']);
}

function karma_minus($post_id)
{
	global $forum_db, $forum_user, $forum_config, $lang_pun_karma;

	if ($forum_config['o_pun_karma_minus_cancel'])
		message($lang_pun_karma['Minus mark cancel']);

	//Check if user tries to vote for his own post
	$query = array(
		'SELECT'	=> '1',
		'FROM'		=> 'posts',
		'WHERE'		=> 'poster_id = '.$forum_user['id'].' AND id = '.$post_id
	);
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	if ($forum_db->num_rows($result) > 0)
		message($lang_pun_karma['Vote error']);

	//Check if user voted yet
	$query = array(
		'SELECT'	=> '1',
		'FROM'		=> 'pun_karma',
		'WHERE'		=> 'user_id = '.$forum_user['id'].' AND post_id = '.$post_id
	);
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	if (!$forum_db->num_rows($result))
	{
		$query = array(
			'INSERT'		=> 'user_id, post_id, mark, updated_at',
			'INTO'			=> 'pun_karma',
			'VALUES'		=> $forum_user['id'].', '.$post_id.', -1, '.time()
		);
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		//This query is needed to set num posts to 0 for correct karma calcualation.
		$query = array(
			'UPDATE'		=> 'posts',
			'SET'			=> 'karma = 0',
			'WHERE'			=> 'karma IS NULL AND id = '.$post_id
		);
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		$query = array(
			'UPDATE'		=> 'posts',
			'SET'			=> 'karma = karma - 1',
			'WHERE'			=> 'id = '.$post_id
		);
		$forum_db->query_build($query) or error(__FILE__, __LINE__);
	}
	else
		message($lang_pun_karma['Already voted']);
};

function karma_cancel($post_id)
{
	global $forum_db, $forum_user, $lang_pun_karma;

	//Check if user tries to vote for his own post
	$query = array(
		'SELECT'	=> '1',
		'FROM'		=> 'posts',
		'WHERE'		=> 'poster_id = '.$forum_user['id'].' AND id = '.$post_id
	);
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	if ($forum_db->num_rows($result) > 0)
		message($lang_pun_karma['Vote error']);

	$query = array(
		'SELECT'	=> 'mark',
		'FROM'		=> 'pun_karma',
		'WHERE'		=> 'user_id = '.$forum_user['id'].' AND post_id = '.$post_id
	);
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	if (!$forum_db->num_rows($result))
		message($lang_pun_karma['Cancel error']);

	list($prev_mark) = $forum_db->fetch_row($result);
	$query = array(
		'DELETE'		=> 'pun_karma',
		'WHERE'			=> 'user_id = '.$forum_user['id'].' AND post_id = '.$post_id
	);
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$query = array(
		'UPDATE'		=> 'posts',
		'SET'			=> 'karma = karma - '.$prev_mark,
		'WHERE'			=> 'id = '.$post_id
	);
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
}

?>
