<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
function app_count_tid($tid)
{
	global $forum_db;

	$query_app = array(
		'SELECT'	=> 'COUNT(id)',
		'FROM'		=> 'posts',
		'WHERE'		=> 'topic_id='.$tid
	);

	$result_app = $forum_db->query_build($query_app) or error(__FILE__, __LINE__);

	if ($forum_db->num_rows($result_app))
	{
		$count = $forum_db->result($result_app);

		return $count;
	}
	else
		return -1;
}

function app_count_tid_approval($tid)
{
	global $forum_db;

	$query_app = array(
		'SELECT'	=> 'COUNT(id)',
		'FROM'		=> 'post_approval_posts',
		'WHERE'		=> 'topic_id='.$tid
	);

	$result_app = $forum_db->query_build($query_app) or error(__FILE__, __LINE__);

	if ($forum_db->num_rows($result_app))
	{
		$count = $forum_db->result($result_app);

		return $count;
	}
	else
		return -1;
}

function add_message( $app_id )
{
	global $forum_db;

	$query = array(
		'SELECT'	=> '*',
		'FROM'		=> 'post_approval_posts',
		'WHERE'		=> 'id = '.$app_id
	);
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	if (!$forum_db->num_rows($result))
		return false;

	$row = $forum_db->fetch_assoc($result) or error(__FILE__, __LINE__);
	$post_info = array(
		'is_guest'		=> ($row['id'] == 1) ? (true) : (false),
		'poster'		=> $row['poster'],
		'poster_id'		=> $row['poster_id'],
		'poster_email'	=> $row['poster_email'],
		'message'		=> $row['message'],
		'hide_smilies'	=> $row['hide_smilies'],
		'posted'		=> $row['posted'],
		'topic_id'		=> $row['topic_id'],
		'subscr_action'	=> 0
	);
	$query = array(
		'SELECT'	=> 'forum_id',
		'FROM'		=> 'topics',
		'WHERE'		=> 'id = '.$row['topic_id']
	);
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$fid = $forum_db->fetch_assoc($result) or error(__FILE__, __LINE__);
	$post_info['forum_id'] = $fid['forum_id'];

	// Add the post
	$query = array(
		'INSERT'	=> 'poster, poster_id, poster_ip, message, hide_smilies, posted, topic_id',
		'INTO'		=> 'posts',
		'VALUES'	=> '\''.$forum_db->escape($post_info['poster']).'\', '.$post_info['poster_id'].', \''.$forum_db->escape(get_remote_address()).'\', \''.$forum_db->escape($post_info['message']).'\', '.$post_info['hide_smilies'].', '.$post_info['posted'].', '.$post_info['topic_id']
	);

	// If it's a guest post, there might be an e-mail address we need to include
	if ($post_info['is_guest'] && $post_info['poster_email'] != null)
	{
		$query['INSERT'] .= ', poster_email';
		$query['VALUES'] .= ', \''.$forum_db->escape($post_info['poster_email']).'\'';
	}

	$forum_db->query_build($query) or error(__FILE__, __LINE__);
	$new_pid = $forum_db->insert_id();

	$query = array(
		'UPDATE'	=> 'posts',
		'SET'		=> 'id='.$app_id,
		'WHERE'		=> 'id='.$new_pid
	);

	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	$new_pid = $app_id;

	if (!$post_info['is_guest'])
	{
		// Subscribe or unsubscribe?
		if ($post_info['subscr_action'] == 1)
		{
			$query = array(
				'INSERT'	=> 'user_id, topic_id',
				'INTO'		=> 'subscriptions',
				'VALUES'	=> $post_info['poster_id'].' ,'.$post_info['topic_id']
			);

			$forum_db->query_build($query) or error(__FILE__, __LINE__);
		}
		else if ($post_info['subscr_action'] == 2)
		{
			$query = array(
				'DELETE'	=> 'subscriptions',
				'WHERE'		=> 'topic_id='.$post_info['topic_id'].' AND user_id='.$post_info['poster_id']
			);

			$forum_db->query_build($query) or error(__FILE__, __LINE__);
		}
	}

	// Count number of replies in the topic
	$query = array(
		'SELECT'	=> 'COUNT(p.id)',
		'FROM'		=> 'posts AS p',
		'WHERE'		=> 'p.topic_id='.$post_info['topic_id']
	);

	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$num_replies = $forum_db->result($result, 0) - 1;

	// Update topic
	$query = array(
		'UPDATE'	=> 'topics',
		'SET'		=> 'num_replies='.$num_replies.', last_post='.$post_info['posted'].', last_post_id='.$new_pid.', last_poster=\''.$forum_db->escape($post_info['poster']).'\'',
		'WHERE'		=> 'id='.$post_info['topic_id']
	);

	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	sync_forum($post_info['forum_id']);

	if (!defined('FORUM_SEARCH_IDX_FUNCTIONS_LOADED'))
		require FORUM_ROOT.'include/search_idx.php';

	update_search_index('post', $new_pid, $post_info['message']);

	send_subscriptions($post_info, $new_pid);

	// Increment user's post count & last post time
	if (isset($post_info['update_user']))
	{
		if ($post_info['is_guest'])
		{
			$query = array(
				'UPDATE'	=> 'online',
				'SET'		=> 'last_post='.$post_info['posted'],
				'WHERE'		=> 'ident=\''.$forum_db->escape(get_remote_address()).'\''
			);
		}
		else
		{
			$query = array(
				'UPDATE'	=> 'users',
				'SET'		=> 'num_posts=num_posts+1, last_post='.$post_info['posted'],
				'WHERE'		=> 'id='.$post_info['poster_id']
			);
		}

		$forum_db->query_build($query) or error(__FILE__, __LINE__);
	}

	// If the posting user is logged in update his/her unread indicator
	if (!$post_info['is_guest'] && isset($post_info['update_unread']) && $post_info['update_unread'])
	{
		$tracked_topics = get_tracked_topics();
		$tracked_topics['topics'][$post_info['topic_id']] = time();
		set_tracked_topics($tracked_topics);
	}
}

function extension_action()
{
    global $forum_db, $forum_user, $forum_url, $lang_common, $lang_app_post, $forum_config, $lang_forum, $lang_topic,
		$base_url, $forum_page, $cur_forum;

	$aptid = isset($_GET['aptid']) ? intval($_GET['aptid']) : 0;
	$del = isset($_GET['del']) ? intval($_GET['del']) : 0;
	$app = isset($_GET['app']) ? intval($_GET['app']) : 0;
	$appid = isset($_GET['appid']) ? intval($_GET['appid']) : 0;
	$p_cr = isset($_GET['p']) ? intval($_GET['p']) : 0;
	$topics = isset($_GET['topics']) ? intval($_GET['topics']) : 0;
	$all_post = isset($_GET['all_post']) ? intval($_GET['all_post']) : 0;
	$errors = array();

	require FORUM_ROOT.'lang/'.$forum_user['language'].'/topic.php';
	require FORUM_ROOT.'lang/'.$forum_user['language'].'/profile.php';
	require FORUM_ROOT.'lang/'.$forum_user['language'].'/common.php';
	require FORUM_ROOT.'lang/'.$forum_user['language'].'/misc.php';
	require FORUM_ROOT.'extensions/pun_approval/post_app_url.php';

	if (($aptid < 0) || ($del < 0) || ($app < 0) || ($appid < 0))
		$errors[] = $lang_app_post['Bad address argument'];

        if (isset($_GET['del']))
            delete_unapproved_post();
        if (isset($_GET['app']))
            approve_post();


        


}
function show_unapproved_posts()
{
    global $forum_db, $forum_user, $forum_url, $lang_common, $lang_app_post, $forum_config, $lang_forum, $lang_topic,
		$base_url, $forum_page, $cur_forum;

	$aptid = isset($_GET['aptid']) ? intval($_GET['aptid']) : 0;
	$del = isset($_GET['del']) ? intval($_GET['del']) : 0;
	$app = isset($_GET['app']) ? intval($_GET['app']) : 0;
	$appid = isset($_GET['appid']) ? intval($_GET['appid']) : 0;
	$p_cr = isset($_GET['p']) ? intval($_GET['p']) : 0;
	$topics = isset($_GET['topics']) ? intval($_GET['topics']) : 0;
	$all_post = isset($_GET['all_post']) ? intval($_GET['all_post']) : 0;
	$errors = array();

	require FORUM_ROOT.'lang/'.$forum_user['language'].'/topic.php';
	require FORUM_ROOT.'lang/'.$forum_user['language'].'/profile.php';
	require FORUM_ROOT.'lang/'.$forum_user['language'].'/common.php';
	require FORUM_ROOT.'lang/'.$forum_user['language'].'/misc.php';
	require FORUM_ROOT.'extensions/pun_approval/post_app_url.php';

	if (($aptid < 0) || ($del < 0) || ($app < 0) || ($appid < 0))
		$errors[] = $lang_app_post['Bad address argument'];
    
    
        if (!$aptid && !$appid && !$all_post)
	{
		$forum_page['num_pages'] = ceil($cur_forum['num_topics'] / $forum_user['disp_topics']);
		$forum_page['page'] = (!isset($_GET['p']) || !is_numeric($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $forum_page['num_pages']) ? 1 : $_GET['p'];
		$forum_page['start_from'] = $forum_user['disp_topics'] * ($forum_page['page'] - 1);
		$forum_page['finish_at'] = min(($forum_page['start_from'] + $forum_user['disp_topics']), ($cur_forum['num_topics']));
		$forum_page['items_info'] = generate_items_info($lang_forum['Topics'], ($forum_page['start_from'] + 1), $cur_forum['num_topics']);

		// Fetch list of topics
		$query_app_post = array(
			'SELECT'	=> 'DISTINCT t.id, t.poster, t.subject, t.posted, t.first_post_id, t.last_post, t.last_post_id, t.last_poster, t.num_views, t.num_replies, t.closed, t.sticky, t.moved_to',
			'FROM'		=> 'post_approval_topics AS t',
			'JOINS'		=> array(
				array(
					'INNER JOIN'	=> 'forums AS f',
					'ON'			=> 't.forum_id=f.id'
				),
			),
			'WHERE'		=> '(t.num_replies>0 OR t.first_post_id>0)',
			'ORDER BY'	=> 't.last_post DESC'
		);

		$result_app_topic = $forum_db->query_build($query_app_post) or error(__FILE__, __LINE__);

		$forum_page['item_header'] = array();
		$forum_page['item_header']['subject']['title'] = '<strong class="subject-title">'.$lang_forum['Topics'].'</strong>';
		$forum_page['item_header']['info']['replies'] = '<strong class="info-replies">'.$lang_forum['replies'].'</strong>';

		if ($forum_config['o_topic_views'] == '1')
			$forum_page['item_header']['info']['views'] = '<strong class="info-views">'.$lang_forum['views'].'</strong>';

		$forum_page['item_header']['info']['lastpost'] = '<strong class="info-lastpost">'.$lang_forum['last post'].'</strong>';

		$line_subtitles = sprintf($lang_forum['Forum subtitle'], implode(' ', $forum_page['item_header']['subject']), implode(', ', $forum_page['item_header']['info']));

		?>

		<div class="main-subhead">
			<h2 class="hn"><span><strong><?php echo $lang_app_post['Unp topics'] ?></strong></span></h2>
		</div>
		<div class="main-content main-forum">

		<?php
		// If there were any errors, show them
		if (!empty($errors))
		{
			$forum_page['errors'] = array();
			foreach ($errors as $cur_error)
				$forum_page['errors'][] = '<li class="warn"><span>'.$cur_error.'</span></li>';

			($hook = get_hook('po_pre_post_errors')) ? eval($hook) : null;

			?>
				<div class="ct-box error-box">
					<h2 class="warn hn"><?php echo $lang_app_post['Post errors'] ?></h2>
					<ul class="error-list">
						<?php echo implode("\n\t\t\t\t", $forum_page['errors'])."\n" ?>
					</ul>
				</div>
			<?php

		}
		?>

		<?php

			if ($forum_db->num_rows($result_app_topic))
			{
				$forum_page['item_count'] = 0;

				while ($cur_topic = $forum_db->fetch_assoc($result_app_topic))
				{
					++$forum_page['item_count'];

					// Start from scratch
					$forum_page['item_subject'] = $forum_page['item_status'] = $forum_page['item_last_post'] = $forum_page['item_alt_message'] = $forum_page['item_nav'] = array();
					$forum_page['item_indicator'] = '';
					$forum_page['item_alt_message'][] = $lang_forum['Topics'].' '.($forum_page['item_count']);

					if ($forum_config['o_censoring'] == '1')
						$cur_topic['subject'] = censor_words($cur_topic['subject']);

					// Should we display the dot or not? :)
					if (!$forum_user['is_guest'] && $forum_config['o_show_dot'] == '1' && $cur_topic['has_posted'] > 0)
					{
						$forum_page['item_title']['posted'] = '<span class="posted-mark">'.$lang_forum['You posted indicator'].'</span>';
						$forum_page['item_status']['posted'] = 'posted';
					}

					if ($cur_topic['sticky'] == '1')
					{
						$forum_page['item_title_status']['sticky'] = '<em class="sticky">'.$lang_forum['Sticky'].'</em>';
						$forum_page['item_status']['sticky'] = 'sticky';
					}

					if ($cur_topic['closed'] == '1')
					{
						$forum_page['item_title_status']['closed'] = '<em class="closed">'.$lang_forum['Closed'].'</em>';
						$forum_page['item_status']['closed'] = 'closed';
					}

					if (!empty($forum_page['item_title_status']))
						$forum_page['item_title']['status'] = '<span class="item-status">'.sprintf($lang_forum['Item status'], implode(', ', $forum_page['item_title_status'])).'</span>';

					$forum_page['item_title']['link'] = '<a href="'.forum_link($post_app_url['Permalink topic'], array($cur_topic['id'], sef_friendly($cur_topic['subject']))).'">'.forum_htmlencode($cur_topic['subject']).'</a>';

					$forum_page['item_body']['subject']['title'] = '<h3 class="hn"><span class="item-num">'.forum_number_format($forum_page['start_from'] + $forum_page['item_count']).'</span> '.implode(' ', $forum_page['item_title']).'</h3>';

					// Assemble the Topic subject

					if (empty($forum_page['item_status']))
						$forum_page['item_status']['normal'] = 'normal';

					$forum_page['item_pages'] = ceil(($cur_topic['num_replies'] + 1) / $forum_user['disp_posts']);

					if ($forum_page['item_pages'] > 1)
						$forum_page['item_nav']['pages'] = '<span>'.$lang_forum['Pages'].'&#160;</span>'.paginate($forum_page['item_pages'], -1, $post_app_url['Permalink topic'], $lang_common['Page separator'], array($cur_topic['id'], sef_friendly($cur_topic['subject'])));

					if (!empty($forum_page['item_nav']))
						$forum_page['item_subject']['nav'] = '<span class="item-nav">'.sprintf($lang_forum['Topic navigation'], implode('&#160;&#160;', $forum_page['item_nav'])).'</span>';

					$forum_page['item_body']['info']['replies'] = '<li class="info-replies"><strong>'.forum_number_format($cur_topic['num_replies']).'</strong> <span class="label">'.(($cur_topic['num_replies'] == 1) ? $lang_forum['reply'] : $lang_forum['replies']).'</span></li>';

					$forum_page['item_body']['info']['lastpost'] = '<li class="info-lastpost"><span class="label">'.$lang_forum['Last post'].'</span> <strong>'.(($cur_topic['last_post'] != 0) ? '<a href="'.forum_link($post_app_url['Permalink post'], $cur_topic['last_post_id']).'">'.format_time($cur_topic['last_post']).'</a>' : $lang_app_post['Never']).'</strong> <cite>'.sprintf($lang_forum['by poster'], forum_htmlencode($cur_topic['last_poster'])).'</cite></li>';

					$forum_page['item_subject']['starter'] = '<span class="item-starter">'.sprintf($lang_forum['Topic starter'], '<cite>'.forum_htmlencode($cur_topic['poster']).'</cite>').'</span>';
					$forum_page['item_body']['subject']['desc'] = implode(' ', $forum_page['item_subject']);

					$forum_page['item_style'] = (($forum_page['item_count'] % 2 != 0) ? ' odd' : ' even').(($forum_page['item_count'] == 1) ? ' main-first-item' : '').((!empty($forum_page['item_status'])) ? ' '.implode(' ', $forum_page['item_status']) : '');

					?>

						<div id="topic<?php echo $cur_topic['id'] ?>" class="main-item<?php echo $forum_page['item_style'] ?>">
							<span class="icon <?php echo implode(' ', $forum_page['item_status']) ?>"><!-- --></span>
							<div class="item-subject">
								<?php echo implode("\n\t\t\t\t", $forum_page['item_body']['subject'])."\n" ?>
							</div>
							<ul class="item-info">
								<?php echo implode("\n\t\t\t\t", $forum_page['item_body']['info'])."\n" ?>
							</ul>
						</div>
					<?php

				}

				?>
					<div class="main-subhead">
						<h3 class="hn"><span><a href="<?php echo forum_link($post_app_url['Posts section']) ?>"><?php echo $lang_app_post['See posts']?></a></span></h2>
					</div>
				<?php
			}
			else
			{
				$result_app_topic_app_post = $forum_db->query_build($query_app_post) or error(__FILE__, __LINE__);


				?>
					<div class="ct-box error-box">
						<ul class="error-list">
							<?php echo $lang_app_post['no posts']; ?>
						</ul>
					</div>
				<?php

			}

			?>
		</div>

		<?php

	}

	$id = 0;


        if ($aptid || $appid || $all_post)
        {
                $forum_page = array();
                $posts_check = array();
                // Determine on what page the post is located (depending on $forum_user['disp_posts'])
                $query = array(
                        'SELECT'	=> 'COUNT(id)',
                        'FROM'		=> 'post_approval_posts',
                        'WHERE'		=> 'topic_id='.$aptid
                );

                if ($all_post)
                {
                        $query = array(
                                'SELECT'	=> 'COUNT(p.id)',
                                'FROM'		=> 'post_approval_posts AS p',
                                'JOINS'		=> array(
                                        array(
                                                'INNER JOIN'	=> 'post_approval_topics AS t',
                                                'ON'			=> 't.first_post_id<>p.id'
                                        ),
                                ),
                                'WHERE'		=> 'p.topic_id='.$aptid
                        );
                }

                $result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

                if (!$forum_db->num_rows($result))
                        $errors[] = $lang_app_post['No db result from posts'];
                else
                        $num_posts = $forum_db->result($result);

                if ($all_post)
                {
                        $query_app = array(
                                'SELECT'	=> 'id',
                                'FROM'		=> 'post_approval_topics'
                        );

                        $result = $forum_db->query_build($query_app) or error(__FILE__, __LINE__);

                        if ($forum_db->num_rows($result))
                                while($row = $forum_db->fetch_assoc($result))
                                {
                                        $posts_check[] = $row['id'];
                                }
                }

                // Fetch some info about the topic
                $query = array(
                        'SELECT'	=> 't.subject, t.posted, t.poster, t.first_post_id, t.closed, t.num_replies, t.sticky, f.id AS forum_id, f.forum_name, f.moderators, fp.post_replies',
                        'FROM'		=> 'post_approval_topics AS t',
                        'JOINS'		=> array(
                                array(
                                        'INNER JOIN'	=> 'forums AS f',
                                        'ON'			=> 'f.id=t.forum_id'
                                ),
                                array(
                                        'LEFT JOIN'		=> 'forum_perms AS fp',
                                        'ON'			=> '(fp.forum_id=f.id AND fp.group_id='.$forum_user['g_id'].')'
                                )
                        ),
                        'WHERE'		=> '(fp.read_forum IS NULL OR fp.read_forum=1) AND t.id='.$aptid.' AND t.moved_to IS NULL'
                );

                if ($all_post)
                        $query['WHERE'] = '(fp.read_forum IS NULL OR fp.read_forum=1) AND (t.id IN ('.implode(', ', array_values($posts_check)).')) AND (t.moved_to IS NULL)';

                $result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
                if (!$forum_db->num_rows($result))
                        $errors[] = $lang_app_post['Error app topic'];

                $cur_topic = $forum_db->fetch_assoc($result);

                $forum_page['num_pages'] = ceil($num_posts / $forum_user['disp_posts']);
                $forum_page['page'] = (!isset($_GET['page']) || !is_numeric($_GET['page']) || $_GET['page'] <= 1 || $_GET['page'] > $forum_page['num_pages']) ? 1 : $_GET['page'];
                $forum_page['start_from'] = $forum_user['disp_posts'] * ($forum_page['page'] - 1);
                $forum_page['finish_at'] = min(($forum_page['start_from'] + $forum_user['disp_posts']), $num_posts);
                $forum_page['main_info'] = sprintf($lang_common['Page info'], $lang_common['Posts'], $forum_page['start_from'] + 1, $forum_page['finish_at'], $num_posts );

                $query = array(
                        'SELECT'	=> 't.subject, u.email, u.title, u.url, u.location, u.signature, u.email_setting, u.num_posts, u.registered, u.admin_note, p.id, p.poster AS username, p.poster_id, p.poster_ip, p.poster_email, p.message, p.hide_smilies, p.posted, p.edited, p.edited_by, g.g_id, g.g_user_title, o.user_id AS is_online',
                        'FROM'		=> 'post_approval_posts AS p',
                        'JOINS'		=> array(
                                array(
                                        'INNER JOIN'	=> 'users AS u',
                                        'ON'			=> 'u.id=p.poster_id'
                                ),
                                array(
                                        'INNER JOIN'	=> 'groups AS g',
                                        'ON'			=> 'g.g_id=u.group_id'
                                ),
                                array(
                                        'LEFT JOIN'		=> 'online AS o',
                                        'ON'			=> '(o.user_id=u.id AND o.user_id!=1 AND o.idle=0)'
                                ),
                                array(
                                        'INNER JOIN'	=> 'post_approval_topics AS t',
                                        'ON'			=> 't.id=p.topic_id'
                                ),
                        ),
                        'ORDER BY'	=> 'p.id',
                        'LIMIT'		=> $forum_page['start_from'].','.$forum_user['disp_posts']
                );

                if (!$all_post && $aptid)
                {
                        $query['WHERE'] = 'p.topic_id='.$aptid;
                }

                $result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

                // Setup breadcrumbs
                $forum_page['crumbs'] = array(
                        array($forum_config['o_board_title'], forum_link($forum_url['index'])),
                        array($cur_forum['forum_name'], forum_link($forum_url['forum'], array($id, sef_friendly($cur_forum['forum_name']))))
                );

                $forum_page['main_head_options']['select_all'] = '<span '.(empty($forum_page['main_head_options']) ? ' class="first-item"' : '').'></span>';

                if ($forum_page['num_pages'] > 1)
                        $forum_page['main_head_pages'] = sprintf($lang_common['Page info'], $forum_page['page'], $forum_page['num_pages']);

                // Generate paging links
                $forum_page['page_post']['paging'] = '<p class="paging"><span class="pages">'.$lang_common['Pages'].'</span> '.paginate($forum_page['num_pages'], $forum_page['page'], $forum_url['moderate_topic'], $lang_common['Paging separator'], array($cur_topic['forum_id'], $aptid)).'</p>';

                // Navigation links for header and page numbering for title/meta description
                if ($forum_page['page'] < $forum_page['num_pages'])
                {
                        $forum_page['nav']['last'] = '<link rel="last" href="'.forum_sublink($forum_url['moderate_topic'], $forum_url['page'], $forum_page['num_pages'], array($fid, $tid)).'" title="'.$lang_common['Page'].' '.$forum_page['num_pages'].'" />';
                        $forum_page['nav']['next'] = '<link rel="next" href="'.forum_sublink($forum_url['moderate_topic'], $forum_url['page'], ($forum_page['page'] + 1), array($fid, $tid)).'" title="'.$lang_common['Page'].' '.($forum_page['page'] + 1).'" />';
                }
                if ($forum_page['page'] > 1)
                {
                        $forum_page['nav']['prev'] = '<link rel="prev" href="'.forum_sublink($forum_url['moderate_topic'], $forum_url['page'], ($forum_page['page'] - 1), array($fid, $tid)).'" title="'.$lang_common['Page'].' '.($forum_page['page'] - 1).'" />';
                        $forum_page['nav']['first'] = '<link rel="first" href="'.forum_link($forum_url['moderate_topic'], array($fid, $tid)).'" title="'.$lang_common['Page'].' 1" />';
                }

                $forum_page['items_info'] = ($num_posts) ? generate_items_info($lang_misc['Posts'], ($forum_page['start_from']), (($all_post) ? $num_posts : intval($cur_topic['num_replies']))) : $lang_misc['Posts'];

                if (!$all_post)
                        $forum_page['form_action'] = forum_link($post_app_url['Permalink topic'], $aptid);
                else
                        $forum_page['form_action'] = forum_link($post_app_url['Posts section']);

                $forum_page['hidden_fields'] = array(
                        'form_sent'		=> '<input type="hidden" name="form_sent" value="1" />',
                        'form_user'		=> '<input type="hidden" name="form_user" value="'.((!$forum_user['is_guest']) ? forum_htmlencode($forum_user['username']) : 'Guest').'" />',
                        'csrf_token'	=> '<input type="hidden" name="csrf_token" value="'.generate_form_token($forum_page['form_action']).'" />'
                );

                ?>

                <div class="main-subhead">
                        <h2 class="hn"><span><strong><?php echo $lang_app_post['Unp posts'] ?></strong></span></h2>
                </div>

        <?php


                if (!$forum_db->num_rows($result))
                {

                ?>
                        <div class="main-content main-forum">
                                <div class="ct-box error-box">
                                        <ul class="error-list">
                                                <?php echo $lang_app_post['No posts']; ?>
                                        </ul>
                                </div>
                        </div>
                <?php

                }
                else
                {
                        ?>
                        <div class="main-head">
                        <?php
                                if (!empty($forum_page['main_head_options']))
                                        echo "\n\t\t".'<p class="options">'.implode(' ', $forum_page['main_head_options']).'</p>';
                        ?>
                                <h2 class="hn"><span><?php echo $forum_page['items_info'] ?></span></h2>
                        </div>
                        <div class="main-content main-topic">
                                <form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo $forum_page['form_action'] ?>">
                                        <div class="hidden">
                                                <?php echo implode("\n\t\t\t\t", $forum_page['hidden_fields'])."\n" ?>
                                        </div>
                        <?php
                        $start = $forum_page['start_from'];
                        $forum_page['item_count'] = 0;

                        while ($cur_post = $forum_db->fetch_assoc($result))
                        {
                                $start++;
                                $forum_page['post_ident'] = array();
                                $forum_page['author_ident'] = array();
                                $forum_page['author_info'] = array();
                                $forum_page['post_options'] = array();
                                $forum_page['post_contacts'] = array();
                                $forum_page['post_actions'] = array();
                                $forum_page['message'] = array();
                                $forum_page['item_count']++;

                                // Give the post some class
                                $forum_page['item_status'] = array(
                                        'post',
                                        ($forum_page['item_count'] % 2 != 0) ? 'odd' : 'even'
                                );

                                if ($forum_page['item_count'] == 1)
                                        $forum_page['item_status']['firstpost'] = 'firstpost';

                                if (($forum_page['start_from'] + $forum_page['item_count']) == $forum_page['finish_at'])
                                        $forum_page['item_status']['lastpost'] = 'lastpost';

                                if ($cur_post['id'] == $cur_topic['first_post_id'])
                                        $forum_page['item_status']['topicpost'] = 'topicpost';
                                else
                                        $forum_page['item_status']['replypost'] = 'replypost';

                                if (!$all_post)
                                        $forum_page['item_select'] = '<p class="item-select"><input type="checkbox" id="fld'.$cur_post['id'].'" name="sel_posts[]" value="'.$cur_post['id'].'" /> <label for="fld'.$cur_post['id'].'">'.$lang_misc['Select post'].' '.forum_number_format($forum_page['start_from'] + $forum_page['item_count']).'</label></p>';
                                else
                                        $forum_page['item_select'] = '<p></p>';

                                $forum_page['author_ident']['username'] = '<li class="username">'.(($forum_user['g_view_users'] == '1') ? '<a title="'.sprintf($lang_topic['Go to profile'], forum_htmlencode($cur_post['username'])).'" href="'.forum_link($forum_url['user'], $cur_post['poster_id']).'">'.forum_htmlencode($cur_post['username']).'</a>' : '<strong>'.forum_htmlencode($cur_post['username']).'</strong>').'</li>';

                                // Generate the post heading
                                $forum_page['post_ident']['num'] = '<span class="post-num">'.forum_number_format($forum_page['start_from'] + $forum_page['item_count']).'</span>';

                                if ($cur_post['poster_id'] > 1)
                                        $forum_page['post_ident']['byline'] = '<span class="post-byline">'.sprintf((($cur_post['id'] == $cur_topic['first_post_id']) ? $lang_topic['Topic byline'] : $lang_topic['Reply byline']), (($forum_user['g_view_users'] == '1') ? '<a title="'.sprintf($lang_topic['Go to profile'], forum_htmlencode($cur_post['username'])).'" href="'.forum_link($forum_url['user'], $cur_post['poster_id']).'">'.forum_htmlencode($cur_post['username']).'</a>' : '<strong>'.forum_htmlencode($cur_post['username']).'</strong>')).' '.$forum_page['item_select'].'</span>';
                                else
                                        $forum_page['post_ident']['byline'] = '<span class="post-byline">'.sprintf((($cur_post['id'] == $cur_topic['first_post_id']) ? $lang_topic['Topic byline'] : $lang_topic['Reply byline']), '<strong>'.forum_htmlencode($cur_post['username']).'</strong>').'</span>';

                                $forum_page['user_ident'] = array();
                                $forum_page['user_info'] = array();

                                if (isset($user_data_cache[$cur_post['poster_id']]['author_info']))
                                        $forum_page['author_info'] = $user_data_cache[$cur_post['poster_id']]['author_info'];
                                else
                                {
                                        // Generate author information
                                        if ($cur_post['poster_id'] > 1)
                                        {
                                                if ($forum_config['o_show_user_info'] == '1')
                                                {
                                                        if ($cur_post['location'] != '')
                                                        {
                                                                if ($forum_config['o_censoring'] == '1')
                                                                        $cur_post['location'] = censor_words($cur_post['location']);

                                                                $forum_page['author_info']['from'] = '<li><span>'.$lang_topic['From'].' <strong> '.forum_htmlencode($cur_post['location']).'</strong></span></li>';
                                                        }

                                                        $forum_page['author_info']['registered'] = '<li><span>'.$lang_topic['Registered'].' <strong> '.format_time($cur_post['registered'], 1).'</strong></span></li>';

                                                        if ($forum_config['o_show_post_count'] == '1' || $forum_user['is_admmod'])
                                                                $forum_page['author_info']['posts'] = '<li><span>'.$lang_topic['Posts info'].' <strong> '.forum_number_format($cur_post['num_posts']).'</strong></span></li>';
                                                }

                                                if ($forum_user['is_admmod'])
                                                {
                                                        if ($cur_post['admin_note'] != '')
                                                                $forum_page['author_info']['note'] = '<li><span>'.$lang_topic['Note'].' <strong> '.forum_htmlencode($cur_post['admin_note']).'</strong></span></li>';
                                                }
                                        }
                                }

                                // Generate IP information for moderators/administrators
                                if ($forum_user['is_admmod'])
                                {
                                        $forum_page['author_info']['ip'] = '<li><span>'.$lang_topic['IP'].' <a href="'.forum_link($forum_url['get_host'], $cur_post['id']).'">'.$cur_post['poster_ip'].'</a></span></li>';
                                }

                                // Generate author contact details
                                if ($forum_config['o_show_user_info'] == '1')
                                {
                                        if (isset($user_data_cache[$cur_post['poster_id']]['post_contacts']))
                                                $forum_page['post_contacts'] = $user_data_cache[$cur_post['poster_id']]['post_contacts'];
                                        else
                                        {
                                                if ($cur_post['poster_id'] > 1)
                                                {
                                                        if ($cur_post['url'] != '')
                                                                $forum_page['post_contacts']['url'] = '<span class="user-url'.(empty($forum_page['post_contacts']) ? ' first-item' : '').'"><a class="external" href="'.forum_htmlencode(($forum_config['o_censoring'] == '1') ? censor_words($cur_post['url']) : $cur_post['url']).'">'.sprintf($lang_topic['Visit website'], '<span>'.sprintf($lang_topic['User possessive'], forum_htmlencode($cur_post['username'])).'</span>').'</a></span>';
                                                        if ((($cur_post['email_setting'] == '0' && !$forum_user['is_guest']) || $forum_user['is_admmod']) && $forum_user['g_send_email'] == '1')
                                                                $forum_page['post_contacts']['email'] = '<span class="user-email'.(empty($forum_page['post_contacts']) ? ' first-item' : '').'"><a href="mailto:'.forum_htmlencode($cur_post['email']).'">'.$lang_topic['E-mail'].'<span>&#160;'.forum_htmlencode($cur_post['username']).'</span></a></span>';
                                                        else if ($cur_post['email_setting'] == '1' && !$forum_user['is_guest'] && $forum_user['g_send_email'] == '1')
                                                                $forum_page['post_contacts']['email'] = '<span class="user-email'.(empty($forum_page['post_contacts']) ? ' first-item' : '').'"><a href="'.forum_link($forum_url['email'], $cur_post['poster_id']).'">'.$lang_topic['E-mail'].'<span>&#160;'.forum_htmlencode($cur_post['username']).'</span></a></span>';
                                                }
                                                else
                                                {
                                                        if ($cur_post['poster_email'] != '' && !$forum_user['is_guest'] && $forum_user['g_send_email'] == '1')
                                                                $forum_page['post_contacts']['email'] = '<span class="user-email'.(empty($forum_page['post_contacts']) ? ' first-item' : '').'"><a href="mailto:'.forum_htmlencode($cur_post['poster_email']).'">'.$lang_topic['E-mail'].'<span>&#160;'.forum_htmlencode($cur_post['username']).'</span></a></span>';
                                                }
                                        }

                                        if (!empty($forum_page['post_contacts']))
                                        {
                                                $forum_page['post_contacts']['ban'] = '<span class="user-url"><a href="'.forum_link($forum_url['admin_bans']).'?add_ban='.$cur_post['poster_id'].'">'.$lang_profile['Ban user'].'</a></span>';
                                                $forum_page['post_options']['contacts'] = '<p class="post-contacts">'.implode(' ', $forum_page['post_contacts']).'</p>';
                                        }
                                }


                                // Generate actions of post
                                $forum_page['post_actions']['approve'] = '<span class="approve-post"><a href="'.forum_link($post_app_url['approval'], $cur_post['id']).'">'.$lang_app_post['Approve'].'</a></span>';
                                $forum_page['post_actions']['delete'] = '<span class="delete-post"><a href="'.forum_link($post_app_url['delete'], $cur_post['id']).'">'.$lang_app_post['Remove'].'</a></span>';

                                if (!empty($forum_page['post_actions']))
                                        $forum_page['post_options']['actions'] = '<p class="post-actions">'.implode(' ', $forum_page['post_actions']).'</p>';

                                // Give the post some class
                                $forum_page['item_status'] = array(
                                        'post',
                                        ($forum_page['item_count'] % 2 != 0) ? 'odd' : 'even'
                                );

                                if ($forum_page['item_count'] == 1)
                                        $forum_page['item_status']['firstpost'] = 'firstpost';

                                if (($forum_page['start_from'] + $forum_page['item_count']) == $forum_page['finish_at'])
                                        $forum_page['item_status']['lastpost'] = 'lastpost';

                                if ($cur_post['id'] == $cur_topic['first_post_id'])
                                        $forum_page['item_status']['topicpost'] = 'topicpost';
                                else
                                        $forum_page['item_status']['replypost'] = 'replypost';


                                // Generate the post title
                                if ($cur_post['id'] == $cur_topic['first_post_id'])
                                        $forum_page['item_subject'] = sprintf($lang_topic['Topic title'], $cur_topic['subject']);
                                else
                                        $forum_page['item_subject'] = sprintf($lang_topic['Reply title'], $cur_topic['subject']);

                                $forum_page['item_subject'] = forum_htmlencode($forum_page['item_subject']);

                                // Perform the main parsing of the message (BBCode, smilies, censor words etc)
                                $forum_page['message']['message'] = $cur_post['message'];

                                if ($cur_post['edited'] != '')
                                        $forum_page['message']['edited'] = '<p class="lastedit"><em>'.sprintf($lang_topic['Last edited'], forum_htmlencode($cur_post['edited_by']), format_time($cur_post['edited'])).'</em></p>';

                                // Do signature parsing/caching
                                if ($cur_post['signature'] != '' && $forum_user['show_sig'] != '0' && $forum_config['o_signatures'] == '1')
                                {
                                        if (!isset($signature_cache[$cur_post['poster_id']]))
                                                $signature_cache[$cur_post['poster_id']] = parse_signature($cur_post['signature']);

                                        $forum_page['message']['signature'] = '<div class="sig-content"><span class="sig-line"><!-- --></span>'.$signature_cache[$cur_post['poster_id']].'</div>';
                                }

                                // Do user data caching for the post
                                if ($cur_post['poster_id'] > 1 && !isset($user_data_cache[$cur_post['poster_id']]))
                                {
                                        $user_data_cache[$cur_post['poster_id']] = array(
                                                'author_ident'	=> $forum_page['author_ident'],
                                                'author_info'	=> $forum_page['author_info'],
                                                'post_contacts'	=> $forum_page['post_contacts']
                                        );

                                }

                                $forum_page['user_info']['ban'] = '<li><span><a href="'.forum_link($forum_url['admin_bans']).'?add_ban='.$cur_post['poster_id'].'">'.$lang_profile['Ban user'].'</a></span></li>';
                                $forum_page['user_info']['email'] = '<li><a href="mailto:'.$cur_post['email'].'"><span>'.$lang_topic['E-mail'].'<span>&#160;'.forum_htmlencode($cur_post['username']).'</span></span></a></li>';

                                $forum_page['item_ident'] = array(
                                        'user'	=> '<cite>'.sprintf($lang_topic['Reply title'], forum_htmlencode($cur_post['username'])).'</cite>',
                                        'date'	=> '<span>'.format_time($cur_post['posted']).'</span>'
                                );

                                $forum_page['item_head'] = '<a class="permalink" rel="bookmark" title="'.$lang_topic['Permalink post'].'" href="'.forum_link($post_app_url['Permalink post'], $cur_post['id']).'">'.implode(' ', $forum_page['item_ident']).'</a>';

                                $forum_page['post_ident']['link'] = '<span class="post-link"><a class="permalink" rel="bookmark" title="'.$lang_topic['Permalink post'].'" href="'.forum_link($post_app_url['Permalink post'], $cur_post['id']).'">'.implode(' ', $forum_page['item_ident']).'</a></span>';

                                $forum_page['item_subject'] = $lang_common['Re'].' '.$cur_post['subject'];

                        ?>
                                <div class="<?php echo implode(' ', $forum_page['item_status']) ?>">
                                        <div id="p<?php echo $cur_post['id'] ?>" class="posthead">
                                                <h3 class="hn post-ident"><?php echo implode(' ', $forum_page['post_ident']) ?></h3>
                                        </div>
                                        <div class="postbody<?php echo ($cur_post['is_online'] == $cur_post['poster_id']) ? ' online' : '' ?>">
                                                <div class="post-author">
                                                        <ul class="author-ident">
                                                                <?php echo implode("\n\t\t\t\t\t\t", $forum_page['author_ident'])."\n" ?>
                                                        </ul>
                                                        <ul class="author-info">
                                                                <?php echo implode("\n\t\t\t\t\t\t", $forum_page['author_info'])."\n" ?>
                                                        </ul>
                                                </div>
                                                <div class="post-entry">
                                                        <h4 id="pc<?php echo $cur_post['id'] ?>" class="entry-title hn"><?php echo $forum_page['item_subject'] ?></h4>
                                                        <div class="entry-content">
                                                                <?php echo implode("\n\t\t\t\t\t\t", $forum_page['message'])."\n" ?>
                                                        </div>
                                                </div>
                                        </div>
                                        <div class="postfoot">
                                                <div class="post-options">
                                                        <?php echo implode("\n\t\t\t\t\t", $forum_page['post_options'])."\n" ?>
                                                </div>
                                        </div>
                                </div>

                        <?php

                        }

                        ?>
                                        <div class="frm-buttons">

                                        <?php

                                        if (!$all_post)
                                        {

                                        ?>

                                                <span class="submit"><input type="submit" name="app_sev" value="Approve selected" /></span>
                                                <span class="submit"><input type="submit" name="del_sev" value="Remove selected" /></span>

                                        <?php

                                        }

                                        ?>

                                                <span class="submit"><input type="submit" name="app_all" value="Approve all" /></span>
                                        </div>
                                </form>
                        </div>
                        <div class="main-foot">
                                <?php
                                        if (!empty($forum_page['main_foot_options']))
                                                echo "\n\t\t".'<p class="options">'.implode(' ', $forum_page['main_foot_options']).'</p>';
                                ?>
                                <h2 class="hn"><span><?php echo $forum_page['items_info'] ?></span></h2>
                        </div>
                 <?php
                }
        }
}

function delete_unapproved_post()
{
    global $forum_db,$lang_app_post,$forum_user;
    require FORUM_ROOT.'extensions/pun_approval/post_app_url.php';
    $pid = $_GET['del'];
    $aptid = isset($_GET['aptid']) ? intval($_GET['aptid']) : 0;;
    $query_app = array(
            'SELECT'	=> 'id',
            'FROM'		=> 'post_approval_topics',
            'WHERE'		=> 'first_post_id='.$pid
    );

    $result = $forum_db->query_build($query_app) or error(__FILE__, __LINE__);

    if ($forum_db->num_rows($result))
    {
            $query_app = array(
                    'SELECT'	=> 'p.id, p.topic_id',
                    'FROM'		=> 'post_approval_posts AS p',
                    'WHERE'		=> 'p.id='.$pid
            );

            $result_app = $forum_db->query_build($query_app) or error(__FILE__, __LINE__);
            $row = $forum_db->fetch_assoc($result_app);

            $query_app_post = array(
                    'DELETE'	=> 'post_approval_topics',
                    'WHERE'		=> 'id='.$row['topic_id']
            );

            $forum_db->query_build($query_app_post) or error(__FILE__, __LINE__);

            $query_app_post = array(
                    'DELETE'	=> 'post_approval_posts',
                    'WHERE'		=> 'id='.$row['id']
            );

            $forum_db->query_build($query_app_post) or error(__FILE__, __LINE__);

            $query_app_post = array(
                    'DELETE'	=> 'posts',
                    'WHERE'		=> 'topic_id='.$row['topic_id']
            );

            $forum_db->query_build($query_app_post) or error(__FILE__, __LINE__);
    }
    else
    {
            $query_app_post = array(
                    'SELECT'	=> 'topic_id',
                    'FROM'		=> 'post_approval_posts',
                    'WHERE'		=> 'id='.$pid
            );

            $result_app_post = $forum_db->query_build($query_app_post) or error(__FILE__, __LINE__);
            $top_id = $forum_db->result($result_app_post);
            $aptid = $top_id;

            $query_app_post = array(
                    'DELETE'	=> 'post_approval_posts',
                    'WHERE'		=> 'id='.$pid
            );

            $forum_db->query_build($query_app_post) or error(__FILE__, __LINE__);

            $query_app_post = array(
                    'SELECT'	=> 'id, posted, topic_id',
                    'FROM'		=> 'post_approval_posts',
                    'WHERE'		=> 'topic_id='.$top_id,
                    'ORDER BY'	=> 'posted DESC'
            );

            $result_app_post = $forum_db->query_build($query_app_post) or error(__FILE__, __LINE__);

            if ($forum_db->num_rows($result_app_post))
            {
                    $num_replies = $forum_db->num_rows($result_app_post);

                    $row = $forum_db->fetch_assoc($result_app_post);

                    $query_app_post = array(
                            'UPDATE'	=> 'post_approval_topics',
                            'SET'		=> 'num_replies='.$num_replies.', last_post='.$row['posted'].', last_post_id='.$row['id'],
                            'WHERE'		=> 'id='.$aptid
                    );

                    $forum_db->query_build($query_app_post) or error(__FILE__, __LINE__);
            }
            else
            {
                    $query_app_post = array(
                            'UPDATE'	=> 'post_approval_topics',
                            'SET'		=> 'num_replies=0, last_post=0, last_post_id=0',
                            'WHERE'		=> 'id='.$aptid
                    );

                    $forum_db->query_build($query_app_post) or error(__FILE__, __LINE__);
            }
    }

    redirect(forum_link($post_app_url['Permalink topic'], $aptid), $lang_app_post['delete succesfull']);
	
}

function approve_post()
{
    global $forum_db,$lang_app_post,$forum_user;
    require FORUM_ROOT.'extensions/pun_approval/post_app_url.php';
    $pid = $_GET['app'];
    $query_app = array(
            'SELECT'	=> 'p.id,t.id as tid',
            'FROM'		=> 'post_approval_posts AS p',
            'JOINS'		=> array(
                    array(
                            'INNER JOIN'	=> 'post_approval_topics AS t',
                            'ON'			=> 't.first_post_id=p.id'
                    )
            ),
            'WHERE'		=> 'p.id='.$pid
    );

    $result = $forum_db->query_build($query_app) or error(__FILE__, __LINE__);
    $row=$forum_db->fetch_assoc($result);
    $aptid=$row['tid'];
    if ($forum_db->num_rows($result)) //if this post is a first post of some topic
    {
            $query_app_post = array(
                    'SELECT'	=> 't.forum_id, t.subject, p.poster, p.poster_id, p.poster_ip, p.poster_email, p.message, p.hide_smilies, p.posted, p.edited, p.edited_by, p.topic_id',
                    'FROM'		=> 'post_approval_posts AS p',
                    'JOINS'		=> array(
                            array(
                                    'INNER JOIN'	=> 'post_approval_topics AS t',
                                    'ON'			=> 't.id=p.topic_id'
                            ),
                    ),
                    'WHERE'		=> 'p.id='.$pid
            );

            $result_app_post = $forum_db->query_build($query_app_post) or error(__FILE__, __LINE__);
            $row = $forum_db->fetch_assoc($result_app_post);

            $count_replies = 1;

            $query_app_post = array(
                    'INSERT'	=> 'id, poster, poster_id, poster_ip, message, hide_smilies, posted, topic_id, app_timestamp, app_username',
                    'INTO'		=> 'posts',
                    'VALUES'	=> $pid.', \''.$forum_db->escape($row['poster']).'\', '.$row['poster_id'].', \''.$row['poster_ip'].'\', \''.$forum_db->escape($row['message']).'\', '.$row['hide_smilies'].', '.$row['posted'].', '.$row['topic_id'].', '.time().', \''.$forum_user['username'].'\''
            );

            $forum_db->query_build($query_app_post) or error(__FILE__, __LINE__);
            $new_pid = $forum_db->insert_id();

            $new_subject = str_replace(' (being approved)', '', $row['subject']);

            $query_app = array(
                    'SELECT'	=> 'id, poster, subject, posted, first_post_id, last_post, last_post_id, last_poster, num_views, num_replies, closed, sticky, moved_to, forum_id',
                    'FROM'		=> 'post_approval_topics',
                    'WHERE'		=> 'id='.$aptid
            );

            $result_app = $forum_db->query_build($query_app) or error(__FILE__, __LINE__);

            $cur = $forum_db->fetch_assoc($result_app);

            $query = array(
                    'INSERT'	=> 'id, poster, subject, posted, first_post_id, last_post, last_post_id, last_poster, num_views, num_replies, closed, sticky, moved_to, forum_id',
                    'INTO'		=> 'topics',
                    'VALUES'	=> $aptid.', \''.$forum_db->escape($cur['poster']).'\', \''.$forum_db->escape($cur['subject']).'\', '.$cur['posted'].', '.$cur['first_post_id'].', '.$cur['last_post'].', '.$cur['last_post_id'].', \''.$forum_db->escape($cur['last_poster']).'\', '.$cur['num_views'].', '.$cur['num_replies'].', '.$cur['closed'].', '.$cur['sticky'].', '.(($cur['moved_to'] == '') ? 'NULL' : $cur['moved_to']).', '.$cur['forum_id']
            );

            $forum_db->query_build($query) or error(__FILE__, __LINE__);

            $query_app = array(
                    'UPDATE'	=> 'post_approval_topics',
                    'SET'		=> 'subject=\''.$new_subject.'\', first_post_id=0, last_post_id=0',
                    'WHERE'		=> 'id='.$aptid
            );

            $forum_db->query_build($query_app) or error(__FILE__, __LINE__);

            $query_app = array(
                    'UPDATE'	=> 'topics',
                    'SET'		=> 'subject=\''.$new_subject.'\'',
                    'WHERE'		=> 'id='.$aptid
            );

            $forum_db->query_build($query_app) or error(__FILE__, __LINE__);

            if (!defined('FORUM_SEARCH_IDX_FUNCTIONS_LOADED'))
                    require FORUM_ROOT.'include/search_idx.php';

            update_search_index('post', $new_pid, $row['message'], $new_subject);

            sync_forum($row['forum_id']);

            $query_app = array(
                    'DELETE'	=> 'post_approval_posts',
                    'WHERE'		=> 'id='.$pid
            );

            $forum_db->query_build($query_app) or error(__FILE__, __LINE__);

            // If the posting user is logged in update his/her unread indicator
            $tracked_topics = get_tracked_topics();
            $tracked_topics['topics'][$aptid] = time();
            set_tracked_topics($tracked_topics);
    }
    else
    {
            $query = array(
                    'SELECT'	=> 'p.id, p.poster, p.poster_id, p.poster_ip, p.poster_email, p.message, p.hide_smilies, p.posted, p.edited, p.edited_by, p.topic_id, t.subject',
                    'FROM'		=> 'post_approval_posts AS p',
                    'JOINS'		=> array(
                            array(
                                    'INNER JOIN'	=> 'topics AS t',
                                    'ON'			=> 'p.topic_id=t.id'
                            ),
                    ),
                    'WHERE'		=> 'p.id='.$pid
            );

            $result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

            if (!$forum_db->num_rows($result))
                    $errors[] = $lang_app_post['No db result from posts'];

            $row = $forum_db->fetch_assoc($result);

            $post_info = array(
                    'is_guest'		=> ($row['id'] == 1) ? (true) : (false),
                    'poster'		=> $row['poster'],
                    'poster_id'		=> $row['poster_id'],
                    'poster_email'	=> $row['poster_email'],
                    'message'		=> $row['message'],
                    'hide_smilies'	=> $row['hide_smilies'],
                    'posted'		=> $row['posted'],
                    'topic_id'		=> $row['topic_id'],
                    'subject'		=> $row['subject'],
                    'subscr_action'	=> 0
            );
            $query = array(
                    'SELECT'	=> 'forum_id',
                    'FROM'		=> 'topics',
                    'WHERE'		=> 'id = '.$row['topic_id']
            );
            $result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
            $fid = $forum_db->fetch_assoc($result) or error(__FILE__, __LINE__);
            $post_info['forum_id'] = $fid['forum_id'];

            // Add the post
            $query = array(
                    'INSERT'	=> 'id, poster, poster_id, poster_ip, message, hide_smilies, posted, topic_id',
                    'INTO'		=> 'posts',
                    'VALUES'	=> intval($row['id']).', \''.$forum_db->escape($post_info['poster']).'\', '.$post_info['poster_id'].', \''.$forum_db->escape(get_remote_address()).'\', \''.$forum_db->escape($post_info['message']).'\', '.$post_info['hide_smilies'].', '.$post_info['posted'].', '.$post_info['topic_id']
            );

            // If it's a guest post, there might be an e-mail address we need to include
            if ($post_info['is_guest'] && $post_info['poster_email'] != null)
            {
                    $query['INSERT'] .= ', poster_email';
                    $query['VALUES'] .= ', \''.$forum_db->escape($post_info['poster_email']).'\'';
            }

            $forum_db->query_build($query) or error(__FILE__, __LINE__);
            $new_pid = $forum_db->insert_id();

            if (!$post_info['is_guest'])
            {
                    // Subscribe or unsubscribe?
                    if ($post_info['subscr_action'] == 1)
                    {
                            $query = array(
                                    'INSERT'	=> 'user_id, topic_id',
                                    'INTO'		=> 'subscriptions',
                                    'VALUES'	=> $post_info['poster_id'].' ,'.$post_info['topic_id']
                            );

                            $forum_db->query_build($query) or error(__FILE__, __LINE__);
                    }
                    else if ($post_info['subscr_action'] == 2)
                    {
                            $query = array(
                                    'DELETE'	=> 'subscriptions',
                                    'WHERE'		=> 'topic_id='.$post_info['topic_id'].' AND user_id='.$post_info['poster_id']
                            );

                            $forum_db->query_build($query) or error(__FILE__, __LINE__);
                    }
            }
//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
            $num_replies = app_count_tid($post_info['topic_id']);

            // Update topic 
            $query = array(
                    'UPDATE'	=> 'topics',
                    'SET'		=> 'num_replies='.$num_replies.', last_post='.$post_info['posted'].', last_post_id='.$new_pid.', last_poster=\''.$forum_db->escape($post_info['poster']).'\'',
                    'WHERE'		=> 'id='.$post_info['topic_id']
            );

            $forum_db->query_build($query) or error(__FILE__, __LINE__);

            $num_replies = app_count_tid_approval($post_info['topic_id']);

            $query_app = array(
                    'SELECT'	=> 'id, posted',
                    'FROM'		=> 'post_approval_posts',
                    'WHERE'		=> 'topic_id='.$post_info['topic_id'],
                    'ORDER BY'	=> 'id DESC'
            );

            $result_app = $forum_db->query_build($query_app) or error(__FILE__, __LINE__);
            $tid_app = $forum_db->fetch_assoc($result_app);

            $query = array(
                    'UPDATE'	=> 'post_approval_topics',
                    'SET'		=> 'last_post='.$tid_app['posted'].', last_post_id='.$tid_app['id'].', num_replies='.$num_replies,
                    'WHERE'		=> 'id='.$post_info['topic_id']
            );

            $forum_db->query_build($query) or error(__FILE__, __LINE__);





            //!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
            $query_app_post = array(
                    'DELETE'	=> 'post_approval_posts',
                    'WHERE'		=> 'id='.$pid
            );

            $forum_db->query_build($query_app_post) or error(__FILE__, __LINE__);
            //!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!




            sync_forum($post_info['forum_id']);

            if (!defined('FORUM_SEARCH_IDX_FUNCTIONS_LOADED'))
                    require FORUM_ROOT.'include/search_idx.php';

            update_search_index('post', $new_pid, $post_info['message']);

            send_subscriptions($post_info, $new_pid);

            // Increment user's post count & last post time
            if (isset($post_info['update_user']))
            {
                    if ($post_info['is_guest'])
                    {
                            $query = array(
                                    'UPDATE'	=> 'online',
                                    'SET'		=> 'last_post='.$post_info['posted'],
                                    'WHERE'		=> 'ident=\''.$forum_db->escape(get_remote_address()).'\''
                            );
                    }
                    else
                    {
                            $query = array(
                                    'UPDATE'	=> 'users',
                                    'SET'		=> 'num_posts=num_posts+1, last_post='.$post_info['posted'],
                                    'WHERE'		=> 'id='.$post_info['poster_id']
                            );
                    }

                    $forum_db->query_build($query) or error(__FILE__, __LINE__);
            }

            // If the posting user is logged in update his/her unread indicator
            if (!$post_info['is_guest'] && isset($post_info['update_unread']) && $post_info['update_unread'])
            {
                    $tracked_topics = get_tracked_topics();
                    $tracked_topics['topics'][$post_info['topic_id']] = time();
                    set_tracked_topics($tracked_topics);
            }

            redirect(forum_link($post_app_url['Permalink topic'], $post_info['topic_id']), $lang_app_post['approve succesfull']);
    }

    redirect(forum_link($post_app_url['Section'], $aptid), $lang_app_post['approve succesfull']);
}

