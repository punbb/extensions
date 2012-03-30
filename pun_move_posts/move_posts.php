<?php

/**
 * Pun Move Posts extension file
 *
 * @copyright Copyright (C) 2009-2012 PunBB, partially based on code copyright (C) 2009 FluxBB.org
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_move_posts
 */

$tid = intval($_GET['tid']);
$fid = intval($_GET['fid']);
if ($tid < 1)
	message($lang_common['Bad request']);

// Fetch some info about the topic
$query = array(
	'SELECT'	=> 't.subject, t.poster, t.first_post_id, t.posted, t.num_replies',
	'FROM'		=> 'topics AS t',
	'WHERE'		=> 't.id='.$tid.' AND t.moved_to IS NULL'
);

($hook = get_hook('move_post_qr_get_topic_info')) ? eval($hook) : null;

$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
$cur_topic = $forum_db->fetch_assoc($result);

if (!$cur_topic)
	message($lang_common['Bad request']);

$posts = isset($_POST['posts']) && !empty($_POST['posts']) ? $_POST['posts'] : array();
$posts = array_map('intval', (is_array($posts) ? $posts : explode(',', $posts)));

if (isset($_POST['move_posts']))
{
	if (empty($posts))
		message($lang_misc['No posts selected']);

	// Get topics we can move the posts into
	$query = array(
		'SELECT'	=> 'c.id AS cid, c.cat_name, f.id AS fid, f.forum_name',
		'FROM'		=> 'categories AS c',
		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'forums AS f',
				'ON'			=> 'c.id=f.cat_id'
			),
			array(
				'LEFT JOIN'		=> 'forum_perms AS fp',
				'ON'			=> '(fp.forum_id=f.id AND fp.group_id='.$forum_user['g_id'].')'
			)
		),
		'WHERE'		=> '(fp.read_forum IS NULL OR fp.read_forum=1) AND f.redirect_url IS NULL AND f.num_topics!=0',
		'ORDER BY'	=> 'c.disp_position, c.id, f.disp_position'
	);
	
	($hook = get_hook('move_post_qr_get_forums_can_move_to')) ? eval($hook) : null;
	
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$forum_list = array();
	while ($cur_sel_forum = $forum_db->fetch_assoc($result))
		$forum_list[] = $cur_sel_forum;

	$forum_page['form_action'] = forum_link($forum_url['moderate_topic'], array($fid, $tid));
	$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;
	$forum_page['hidden_fields'] = array(
		'csrf_token'	=> '<input type="hidden" name="csrf_token" value="'.generate_form_token($forum_page['form_action']).'" />',
		'posts'			=> '<input type="hidden" name="posts" value="'.implode(',', $posts).'" />',
		'fid'			=> '<input type="hidden" name="fid" value="'.$fid.'" />',
		'tid'			=> '<input type="hidden" name="fid" value="'.$tid.'" />',
	);

	$forum_page['crumbs'] = array(
		array($forum_config['o_board_title'], forum_link($forum_url['index'])),
		array($cur_forum['forum_name'], forum_link($forum_url['forum'], array($fid, sef_friendly($cur_forum['forum_name'])))),
		array($lang_misc['Moderate forum'], forum_link($forum_url['moderate_forum'], $fid)),
		$lang_pun_move_posts['Move posts']
	);

	//Setup main heading
	define('FORUM_PAGE', 'dialogue');
	require FORUM_ROOT.'header.php';

	// START SUBST - <!-- forum_main -->
	ob_start();

?>
	<div class="main-head">
		<h2 class="hn"><span><?php echo end($forum_page['crumbs']) ?></span></h2>
	</div>
	<div class="main-content main-frm">
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo $forum_page['form_action'] ?>">
			<div class="hidden">
				<?php echo implode("\n\t\t\t\t", $forum_page['hidden_fields'])."\n" ?>
			</div>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box select">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_pun_move_posts['Destination forum']?></span></label><br />
						<span class="fld-input"><select id="<?php echo $forum_page['fld_count'] ?>" name="move_to_forum">
<?php
	$forum_page['cur_category'] = 0;
	foreach ($forum_list as $cur_forum)
	{
		if ($cur_forum['cid'] != $forum_page['cur_category'])	// A new category since last iteration?
		{
			if ($forum_page['cur_category'])
				echo "\t\t\t\t".'</optgroup>'."\n";

			echo "\t\t\t\t".'<optgroup label="'.forum_htmlencode($cur_forum['cat_name']).'">'."\n";
			$forum_page['cur_category'] = $cur_forum['cid'];
		}

		echo "\t\t\t\t".'<option value="'.$cur_forum['fid'].'">'.forum_htmlencode($cur_forum['forum_name']).'</option>'."\n";
	}
?>
				</optgroup>
			</select></span>
					</div>
				</div>

			</fieldset>
			<div class="frm-buttons">
				<span class="submit primary"><input type="submit" name="move_posts_s" value="<?php echo $lang_common['Next'] ?>" /></span>
				<span class="cancel"><input type="submit" name="cancel" value="<?php echo $lang_common['Cancel'] ?>" /></span>
			</div>
		</form>
	</div>

<?php

	$forum_id = $fid;

	$tpl_temp = forum_trim(ob_get_contents());
	$tpl_main = str_replace('<!-- forum_main -->', $tpl_temp, $tpl_main);
	ob_end_clean();
	// END SUBST - <!-- forum_main -->

	require FORUM_ROOT.'footer.php';
}

if (isset($_POST['move_posts_s']))
{
	$move_to_forum = isset($_POST['move_to_forum']) && !empty($_POST['move_to_forum']) ? $_POST['move_to_forum'] : array();

	if (empty($posts))
		message($lang_misc['No posts selected']);

	// Get topics we can move the posts into
	$query = array(
		'SELECT'	=> 'f.id AS fid, f.forum_name as f_name, t.id AS tid, t.subject AS topic_subject',
		'FROM'		=> 'forums AS f',
		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'topics AS t',
				'ON'		=> 'f.id=t.forum_id'
			),
		),
		'WHERE'		=> 'f.id='.$move_to_forum.' AND t.id!='.$tid,
		'ORDER BY'	=> 't.last_post DESC'
	);

	($hook = get_hook('move_post_qr_get_topics_can_move_to')) ? eval($hook) : null;
	
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$forum_list = array();
	while ($cur_sel_forum = $forum_db->fetch_assoc($result))
		$forum_list[] = $cur_sel_forum;

	$forum_page['form_action'] = forum_link($forum_url['moderate_topic'], array($fid, $tid));
	$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;
	$forum_page['hidden_fields'] = array(
		'csrf_token'            => '<input type="hidden" name="csrf_token" value="'.generate_form_token($forum_page['form_action']).'" />',
		'posts'			=> '<input type="hidden" name="posts" value="'.implode(',', $posts).'" />',
		'tid'			=> '<input type="hidden" name="tid" value="'.$tid.'" />',
		'fid'			=> '<input type="hidden" name="tid" value="'.$fid.'" />',
		'move_to_forum'	=> '<input type="hidden" name="tid" value="'.$move_to_forum.'" />'
	);

	$forum_page['crumbs'] = array(
		array($forum_config['o_board_title'], forum_link($forum_url['index'])),
		array($cur_forum['forum_name'], forum_link($forum_url['forum'], array($fid, sef_friendly($cur_forum['forum_name'])))),
		array($lang_misc['Moderate forum'], forum_link($forum_url['moderate_forum'], $fid)),
		$lang_pun_move_posts['Move posts']
	);

	//Setup main heading
	define('FORUM_PAGE', 'dialogue');
	require FORUM_ROOT.'header.php';

	// START SUBST - <!-- forum_main -->
	ob_start();

?>
	<div class="main-head">
		<h2 class="hn"><span><?php echo end($forum_page['crumbs']) ?></span></h2>
	</div>
	<div class="main-content main-frm">
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo $forum_page['form_action'] ?>">
			<div class="hidden">
				<?php echo implode("\n\t\t\t\t", $forum_page['hidden_fields'])."\n" ?>
			</div>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box select">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_pun_move_posts['Destination topic']?></span></label><br />
						<span class="fld-input"><select id="<?php echo $forum_page['fld_count'] ?>" name="move_to_topic">
<?php

	$forum_page['cur_forum'] = 0;
	foreach ($forum_list as $cur_forum)
	{
		if ($cur_forum['fid'] != $forum_page['cur_forum'])	// A new category since last iteration?
		{
			if ($forum_page['cur_forum'])
				echo "\t\t\t\t".'</optgroup>'."\n";

			echo "\t\t\t\t".'<optgroup label="'.forum_htmlencode($cur_forum['f_name']).'">'."\n";
			$forum_page['cur_forum'] = $cur_forum['fid'];
		}

		if ($cur_forum['tid'] != $tid)
			echo "\t\t\t\t".'<option value="'.$cur_forum['tid'].'">'.forum_htmlencode($cur_forum['topic_subject']).'</option>'."\n";
	}
?>
				</optgroup>
			</select></span>
					</div>
				</div>
				<div class="sf-set set2">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld2" name="change_time" value="1" /></span>
						<label for="fld2"><span><?php echo $lang_pun_move_posts['Ignore dates']?></span> <?php echo $lang_pun_move_posts['Ignore text']?></label>
					</div>
				</div>

			</fieldset>
			<div class="frm-buttons">
				<span class="submit primary"><input type="submit" name="move_posts_to" value="<?php echo $lang_misc['Move'] ?>" /></span>
				<span class="cancel"><input type="submit" name="cancel" value="<?php echo $lang_common['Cancel'] ?>" /></span>
			</div>
		</form>
	</div>

<?php

	$forum_id = $fid;

	$tpl_temp = forum_trim(ob_get_contents());
	$tpl_main = str_replace('<!-- forum_main -->', $tpl_temp, $tpl_main);
	ob_end_clean();
	// END SUBST - <!-- forum_main -->

	require FORUM_ROOT.'footer.php';
}

if (isset($_POST['move_posts_to']))
{
	$move_to_topic = isset($_POST['move_to_topic']) && !empty($_POST['move_to_topic']) ? $_POST['move_to_topic'] : array();

	if (empty($posts))
		message($lang_misc['No posts selected']);

	// Move the posts
	$query = array(
		'UPDATE'	=> 'posts',
		'SET'		=> 'topic_id='.$move_to_topic,
		'WHERE'		=> 'id IN('.implode(',', $posts).')'
	);

	if (isset($_POST['change_time']))
		$query['SET'] .= ', posted='.time();

	($hook = get_hook('move_post_qr_update_post')) ? eval($hook) : null;
	
	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	$query = array(
		'SELECT'	=> '*',
		'FROM'		=> 'posts',
		'WHERE'		=> 'id IN('.implode(',', $posts).')',
		'ORDER BY'	=> 'posted'
	);
	
	($hook = get_hook('move_post_qr_get_posts_info')) ? eval($hook) : null;
	
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	while ($cur_post = $forum_db->fetch_assoc($result))
	{
		$values = array();

		foreach ($cur_post as $k => $v)
		{
			if (!(empty($v)||$k=='id'))
				$values[$k]='\''.$forum_db->escape($v).'\'';
		}

		echo var_dump($values);
		$query = array(
			'INSERT'	=> implode(',', array_keys($values)),
			'INTO'		=> 'posts',
			'VALUES'	=> implode(',', $values)
		);
		
		($hook = get_hook('move_post_qr_insert_post')) ? eval($hook) : null;
		
		$forum_db->query_build($query);// or error(__FILE__, __LINE__);

		$new_post_id = $forum_db->insert_id();
		
		($hook = get_hook('move_post_loop_insert_end')) ? eval($hook) : null;
	}

	$query = array(
		'DELETE'	=> 'posts',
		'WHERE'		=> 'id IN('.implode(',', $posts).')'
	);
	
	($hook = get_hook('move_post_qr_delete_posts')) ? eval($hook) : null;
	
	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	sync_topic($tid);
	sync_topic($move_to_topic);
	sync_forum($fid);

	($hook = get_hook('move_post_end_pre_redirect')) ? eval($hook) : null;

	redirect(forum_link($forum_url['topic'], array($tid, sef_friendly($cur_topic['subject']))), 'Move posts');
}