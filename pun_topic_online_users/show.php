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

if (!defined('FORUM')) die();
// IDEA: Handle online users table updates and set topic reading/replying marks right there


// If topic ID is not specified we determine it
if (empty($topic_online_uses_topic_id))
{
	if (empty($pid))
		return;

	$query = array(
		'SELECT'	=> 'p.topic_id, t.subject',
		'FROM'		=> 'posts AS p',
		'JOINS'		=> array(
			array(
				'LEFT JOIN'		=> 'topics AS t',
				'ON'			=> 't.id = p.topic_id'
			)
		),
		'WHERE'		=> 'p.id = '.$pid,
	);

	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	list($tid, $topic_subject) = $forum_db->fetch_row($result);
}
else
	$tid = $topic_online_uses_topic_id;

if (empty($tid))
	return;

if (empty($topic_online_uses_topic_subject))
{
	$query = array(
		'SELECT'	=> 'subject',
		'FROM'		=> 'topics',
		'WHERE'		=> 'id = '.$tid,
	);

	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	list($topic_subject) = $forum_db->fetch_row($result);
}
else
	$topic_subject = $topic_online_uses_topic_subject;

if (empty($topic_subject))
	return;
	
$query = array(
		'SELECT'	=> 'pun_tou_tid',
		'FROM'		=> 'online',
		'WHERE'		=> 'user_id='.$forum_user['id']
);

$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

$reading_users = array();
$reading_guests_count = 0;

$replying_users = array();
$replying_guests_count = 0;

if ($forum_db->num_rows($result))
{
	$online_user_topic = $forum_db->fetch_assoc($result);
	
	$query = array(
		'SELECT'	=> 'user_id, ident, logged, pun_tou_tid, prev_url',
		'FROM'		=> 'online',
		'WHERE'		=> 'pun_tou_tid='.intval($online_user_topic['pun_tou_tid'])
	);
	
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	if ($forum_db->num_rows($result))
	{
		while ($cur_user = $forum_db->fetch_assoc($result))
		{
			if (strpos(isset($rewritten_url) ? $rewritten_url : get_current_url(), 'viewtopic.php') == 'true')
			{
				if ($cur_user['user_id'] == 1)
					$reading_guests_count++;
				else 
					$reading_users[] = '<a href="'.forum_link($forum_url['user'], $cur_user['user_id']).'">'.forum_htmlencode($cur_user['ident']).'</a>';	
			}
			else if (strpos(isset($rewritten_url) ? $rewritten_url : get_current_url(), 'post.php') == 'true')
			{
				if ($cur_user['logged'] + $forum_config['o_timeout_online'] > time())
				{
					if ($cur_user['user_id'] == 1)
						$replying_guests_count++;
					else 
						$replying_users[] = '<a href="'.forum_link($forum_url['user'], $cur_user['user_id']).'">'.forum_htmlencode($cur_user['ident']).'</a>';				
				}
			}
		}
	}
}

if (file_exists($ext_info['path'].'/lang/'.$forum_user['language'].'/'.$ext_info['id'].'.php'))
	require $ext_info['path'].'/lang/'.$forum_user['language'].'/'.$ext_info['id'].'.php';
else
	require $ext_info['path'].'/lang/English/'.$ext_info['id'].'.php';

?>
<div class="brd-main" id="topic_online_users">
	<div class="main-head"><h1 class="hn"><span><?php echo $lang_topic_online_users['Topic info']; ?></span></h1></div>
	<div class="main-content">
		<p><?php echo sprintf($lang_topic_online_users['Readers of topic'], $reading_guests_count, count($reading_users)), (count($reading_users) > 0 ? ' : '.implode(', ', $reading_users) : '') ?></p>
<?php

if ($replying_guests_count + count($replying_users) > 0)
{

?>
		<p><?php echo sprintf($lang_topic_online_users['Repliers of topic'], $replying_guests_count, count($replying_users)), (count($replying_users) > 0 ? ' : '.implode(', ', $replying_users) : '') ?></p>
<?php

}

?>

	</div>
</div>
