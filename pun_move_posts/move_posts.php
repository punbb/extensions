<?php

/**
 * Pun Move Posts extension file
 *
 * @copyright Copyright (C) 2009 PunBB, partially based on code copyright (C) 2009 FluxBB.org
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_move_posts
 */

	$tid = intval($_GET['tid']);
	if ($tid < 1)
		message($lang_common['Bad request']);

// Fetch some info about the topic
	$query = array(
		'SELECT'	=> 't.subject, t.poster, t.first_post_id, t.posted, t.num_replies',
		'FROM'		=> 'topics AS t',
		'WHERE'		=> 't.id='.$tid.' AND t.moved_to IS NULL'
	);

	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	if (!$forum_db->num_rows($result))
		message($lang_common['Bad request']);

	$cur_topic = $forum_db->fetch_assoc($result);
	$posts = isset($_POST['posts']) && !empty($_POST['posts']) ? $_POST['posts'] : array();
	$posts = array_map('intval', (is_array($posts) ? $posts : explode(',', $posts)));

if (isset($_POST['move_posts']))
{
	$posts = isset($_POST['posts']) && is_array($_POST['posts']) ? $_POST['posts'] : array();
	$posts = array_map('intval', $posts);
	if (empty($posts))
		message($lang_misc['No posts selected']);

// Get topics we can move the posts into
	$query = array(
		'SELECT'	=> 'c.id AS cid, c.cat_name, f.id AS fid, f.forum_name, t.id AS tid, t.subject AS topic_subject',
		'FROM'		=> 'categories AS c',
		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'forums AS f',
				'ON'			=> 'c.id=f.cat_id'
			),
			array(
				'LEFT JOIN'		=> 'topics AS t',
				'ON'			=> 'f.id=t.forum_id'
			)
		),
		'WHERE'		=> 't.id!='.$tid,
		'ORDER BY'	=> 'c.disp_position, c.id, f.disp_position'
	);

	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$forum_list = array();
	while ($cur_sel_forum = $forum_db->fetch_assoc($result))
		$forum_list[] = $cur_sel_forum;

	$forum_page['form_action'] = forum_link($forum_url['moderate_topic'], array($fid, $tid));
	$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;
	$forum_page['hidden_fields'] = array(
		'csrf_token'	=> '<input type="hidden" name="csrf_token" value="'.generate_form_token($forum_page['form_action']).'" />',
		'posts'			=> '<input type="hidden" name="posts" value="'.implode(',', $posts).'" />',
		'tid'			=> '<input type="hidden" name="tid" value="'.$tid.'" />',
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
		if ($cur_forum['tid'] != $tid)
			echo "\t\t\t\t".'<option value="'.$cur_forum['tid'].'">'.forum_htmlencode($cur_forum['forum_name']).' => '.forum_htmlencode($cur_forum['topic_subject']).'</option>'."\n";
	}
?>
				</optgroup>
			</select></span>
					</div>
				</div>

			</fieldset>
			<div class="frm-buttons">
				<span class="submit"><input type="submit" name="move_posts_to" value="<?php echo $lang_misc['Move'] ?>" /></span>
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
	$posts = isset($_POST['posts']) && !empty($_POST['posts']) ? $_POST['posts'] : array();
	$posts = array_map('intval', (is_array($posts) ? $posts : explode(',', $posts)));

	$move_to_topic=isset($_POST['move_to_topic']) && !empty($_POST['move_to_topic']) ? $_POST['move_to_topic'] : array();

	if (empty($posts))
		message($lang_misc['No posts selected']);

// Move the posts
	$query = array(
			'UPDATE'	=> 'posts',
			'SET'		=> 'topic_id='.$move_to_topic,
			'WHERE'		=> 'id IN('.implode(',', $posts).')'
	);

	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	sync_topic($tid);
	sync_topic($move_to_topic);
	sync_forum($fid);
	redirect(forum_link($forum_url['topic'], array($tid, sef_friendly($cur_topic['subject']))), 'Move posts');
}

?>