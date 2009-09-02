<?php

/***********************************************************************

	Copyright (C) 2008  PunBB

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

if (!defined('FORUM'))
	die();

function karma_plus($post_id)
{
	global $forum_db, $forum_user;

	//Check if user tries to vote for his own post
	$query = array(
		'SELECT'	=> '1',
		'FROM'		=> 'posts',
		'WHERE'		=> 'poster_id = '.$forum_user['id'].' AND id = '.$post_id
	);
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	if ($forum_db->num_rows($result) > 0)
		return FALSE;

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
	return TRUE;
}

function karma_minus($post_id)
{
	global $forum_db, $forum_user;

	//Check if user tries to vote for his own post
	$query = array(
		'SELECT'	=> '1',
		'FROM'		=> 'posts',
		'WHERE'		=> 'poster_id = '.$forum_user['id'].' AND id = '.$post_id
	);
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	if ($forum_db->num_rows($result) > 0)
		return FALSE;

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
	return TRUE;
};

function karma_cancel($post_id)
{
	global $forum_db, $forum_user;

	//Check if user tries to vote for his own post
	$query = array(
		'SELECT'	=> '1',
		'FROM'		=> 'posts',
		'WHERE'		=> 'poster_id = '.$forum_user['id'].' AND id = '.$post_id
	);
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	if ($forum_db->num_rows($result) > 0)
		return FALSE;

	$query = array(
		'SELECT'	=> 'mark',
		'FROM'		=> 'pun_karma',
		'WHERE'		=> 'user_id = '.$forum_user['id'].' AND post_id = '.$post_id
	);
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	if (!$forum_db->num_rows($result))
		return FALSE;

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
	return TRUE;
}

function delete_post_karma($post_id)
{
	global $forum_db;

	// Delete all marks for the post
	$query = array(
		'DELETE'	=> 'pun_karma',
		'WHERE'		=> 'post_id = '.$post_id
	);
	$forum_db->query_build($query) or error(__FILE__, __LINE__);
}

function delete_topic_karma( $topic_id )
{
	global $forum_db;

	$qdelete = 'DELETE FROM '.$forum_db->prefix.'pun_karma
		USING '.$forum_db->prefix.'pun_karma, '.$forum_db->prefix.'posts
		WHERE '.$forum_db->prefix.'posts.topic_id = '.$topic_id.'
			AND '.$forum_db->prefix.'pun_karma.post_id = '.$forum_db->prefix.'posts.id';

	$forum_db->query( $qdelete ) or error(__FILE__, __LINE__);
}

?>
