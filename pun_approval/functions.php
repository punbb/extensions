<?php
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
		message($lang_common['Bad request']);
			
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
	//add_post($post_info, $new_pid);
	// FUNCTION ADD_POST()

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

function show_unapproved_topics()
{

}

function show_unapproved_posts()
{
	global $forum_db, $forum_user, $forum_url, $lang_common, $lang_app_post, $forum_config, $lang_forum, $lang_topic, 
		$base_url, $forum_page, $cur_forum, $aptid, $pid, $del, $app, $topics;
	
	require FORUM_ROOT.'lang/'.$forum_user['language'].'/topic.php';
	require FORUM_ROOT.'lang/'.$forum_user['language'].'/profile.php';
	require FORUM_ROOT.'lang/'.$forum_user['language'].'/common.php';
	require FORUM_ROOT.'extensions/pun_approval/post_app_url.php';
	
	if (($aptid < 0) || ($del < 0) || ($app < 0) || ($pid < 0))
		message($lang_common['Bad request']);
	
	if (!$aptid)
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
			'WHERE'		=> 't.num_replies>0',
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
			<h2 class="hn"><span><?php echo $lang_app_post['Unp topics'] ?></span></h2>
		</div>
		<div class="main-content main-forum<?php echo ($forum_config['o_topic_views'] == '1') ? ' forum-views' : ' forum-noview' ?>">
		
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

					($hook = get_hook('vf_topic_loop_normal_topic_pre_item_title_status_merge')) ? eval($hook) : null;

					if (!empty($forum_page['item_title_status']))
						$forum_page['item_title']['status'] = '<span class="item-status">'.sprintf($lang_forum['Item status'], implode(', ', $forum_page['item_title_status'])).'</span>';

					$forum_page['item_title']['link'] = '<a href="'.forum_link($post_app_url['Permalink topic'], array($cur_topic['id'], sef_friendly($cur_topic['subject']))).'">'.forum_htmlencode($cur_topic['subject']).'</a>';

					($hook = get_hook('vf_topic_loop_normal_topic_pre_item_title_merge')) ? eval($hook) : null;

					$forum_page['item_body']['subject']['title'] = '<h3 class="hn"><span class="item-num">'.forum_number_format($forum_page['start_from'] + $forum_page['item_count']).'</span> '.implode(' ', $forum_page['item_title']).'</h3>';

					// Assemble the Topic subject

					if (empty($forum_page['item_status']))
						$forum_page['item_status']['normal'] = 'normal';

					$forum_page['item_pages'] = ceil(($cur_topic['num_replies'] + 1) / $forum_user['disp_posts']);

					if ($forum_page['item_pages'] > 1)
						$forum_page['item_nav']['pages'] = '<span>'.$lang_forum['Pages'].'&#160;</span>'.paginate($forum_page['item_pages'], -1, $post_app_url['Permalink topic'], $lang_common['Page separator'], array($cur_topic['id'], sef_friendly($cur_topic['subject'])));

					

					($hook = get_hook('vf_topic_loop_normal_topic_pre_item_nav_merge')) ? eval($hook) : null;

					if (!empty($forum_page['item_nav']))
						$forum_page['item_subject']['nav'] = '<span class="item-nav">'.sprintf($lang_forum['Topic navigation'], implode('&#160;&#160;', $forum_page['item_nav'])).'</span>';

					($hook = get_hook('vf_topic_loop_normal_topic_pre_item_subject_merge')) ? eval($hook) : null;

					$forum_page['item_body']['info']['replies'] = '<li class="info-replies"><strong>'.forum_number_format($cur_topic['num_replies']).'</strong> <span class="label">'.(($cur_topic['num_replies'] == 1) ? $lang_forum['reply'] : $lang_forum['replies']).'</span></li>';

					$forum_page['item_body']['info']['lastpost'] = '<li class="info-lastpost"><span class="label">'.$lang_forum['Last post'].'</span> <strong><a href="'.forum_link($post_app_url['Permalink post'], $cur_topic['last_post_id']).'">'.format_time($cur_topic['last_post']).'</a></strong> <cite>'.sprintf($lang_forum['by poster'], forum_htmlencode($cur_topic['last_poster'])).'</cite></li>';

					$forum_page['item_subject']['starter'] = '<span class="item-starter">'.sprintf($lang_forum['Topic starter'], '<cite>'.forum_htmlencode($cur_topic['poster']).'</cite>').'</span>';
					$forum_page['item_body']['subject']['desc'] = implode(' ', $forum_page['item_subject']);

					($hook = get_hook('vf_row_pre_item_status_merge')) ? eval($hook) : null;

					$forum_page['item_style'] = (($forum_page['item_count'] % 2 != 0) ? ' odd' : ' even').(($forum_page['item_count'] == 1) ? ' main-first-item' : '').((!empty($forum_page['item_status'])) ? ' '.implode(' ', $forum_page['item_status']) : '');

					($hook = get_hook('vf_row_pre_display')) ? eval($hook) : null;
				
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
			}
			else
			{
				$result_app_topic_app_post = $forum_db->query_build($query_app_post) or error(__FILE__, __LINE__);
				
				if (empty($forum_page['item_status']))
					$forum_page['item_status']['normal'] = 'normal';
					
					?>
						<div id="topic" class="main-item">
							<span class="icon <?php echo implode(' ', $forum_page['item_status']) ?>"><!-- --></span>
							<div class="item-subject">
								<?php echo $lang_app_post['no posts'] ?>
							</div>
							<ul class="item-info">
								
							</ul>
						</div>
						
					<?php
				
			}
			
		?>
		
		</div>
		
		<?php
		
	}
	
	$id = 0;
	
	$forum_page['item_count'] = 0;
	
	if (isset($_GET['del']))
	{
		$pid = $_GET['del'];
		
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
			'SELECT'	=> 'num_replies',
			'FROM'		=> 'post_approval_topics',
			'WHERE'		=> 'id='.$top_id
		);
		
		$result_app_post = $forum_db->query_build($query_app_post) or error(__FILE__, __LINE__);
		$num_replies = $forum_db->result($result_app_post) - 1;
		
		$query_app_post = array(
			'SELECT'	=> 'id, posted, topic_id',
			'FROM'		=> 'post_approval_posts',
			'WHERE'		=> 'topic_id='.$top_id,
			'ORDER BY'	=> 'posted DESC'
		);
		
		$result_app_post = $forum_db->query_build($query_app_post) or error(__FILE__, __LINE__);
		
		if ($forum_db->num_rows($result_app_post))
		{
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
		
		redirect(forum_link($post_app_url['Section'], $aptid), $lang_app_post['topic redirect']);
	}
	
	if (isset($_GET['app']))
	{
		$pid = $_GET['app'];
		
		$query_app_post = array(
			'SELECT'	=> 'poster, poster_id, poster_ip, poster_email, message, hide_smilies, posted, edited, edited_by, topic_id',
			'FROM'		=> 'post_approval_posts',
			'WHERE'		=> 'id='.$pid
		);
		
		$result_app_post = $forum_db->query_build($query_app_post) or error(__FILE__, __LINE__);
		$row = $forum_db->fetch_assoc($result_app_post);
		$aptid = $row['topic_id'];
		
		$count_replies = 1;
		
		$query_app_post = array(
			'INSERT'	=> 'id, poster, poster_id, poster_ip, message, hide_smilies, posted, topic_id, app_timestamp, app_username',
			'INTO'		=> 'posts',
			'VALUES'	=> $pid.', \''.$forum_db->escape($row['poster']).'\', '.$row['poster_id'].', \''.$row['poster_ip'].'\', \''.$forum_db->escape($row['message']).'\', '.$row['hide_smilies'].', '.$row['posted'].', '.$row['topic_id'].', '.time().', \''.$forum_user['username'].'\''
		);
		
		$forum_db->query_build($query_app_post) or error(__FILE__, __LINE__);
		$new_pid = $forum_db->insert_id();
		
		$query_app_post = array(
			'SELECT'	=> 'num_replies',
			'FROM'		=> 'topics',
			'WHERE'		=> 'id='.$aptid
		);
		
		$result_app_post = $forum_db->query_build($query_app_post) or error(__FILE__, __LINE__);
		$num_replies = $forum_db->result($result_app_post) + 1;
		
		$query_app_post = array(
			'UPDATE'	=> 'topics',
			'SET'		=> 'num_replies='.$num_replies.', last_post='.$row['posted'].', last_post_id='.$new_pid,
			'WHERE'		=> 'id='.$aptid
		);
		
		$forum_db->query_build($query_app_post) or error(__FILE__, __LINE__);
		
		$query_app_post = array(
			'DELETE'	=> 'post_approval_posts',
			'WHERE'		=> 'id='.$pid
		);
		
		$forum_db->query_build($query_app_post) or error(__FILE__, __LINE__);
		
		$query_app_post = array(
			'SELECT'	=> 'num_replies',
			'FROM'		=> 'post_approval_topics',
			'WHERE'		=> 'id='.$aptid
		);
		
		
		$result_app_post = $forum_db->query_build($query_app_post) or error(__FILE__, __LINE__);
		$num_replies = $forum_db->result($result_app_post) - 1;
		
		$query_app_post = array(
			'SELECT'	=> 'id, posted',
			'FROM'		=> 'post_approval_posts',
			'WHERE'		=> 'topic_id='.$aptid,
			'ORDER BY'	=> 'posted DESC'
		);
		
		$result_app_post = $forum_db->query_build($query_app_post) or error(__FILE__, __LINE__);
		
		if ($forum_db->num_rows($result_app_post))
		{
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
		
		redirect(forum_link($post_app_url['Section'], $aptid), $lang_app_post['topic red app']);
	}
	
	if (isset($_POST['del_sev']))
	{
		if (isset($_POST['sel_posts']))
		{
			$posts_check = $_POST['sel_posts'];
			
			$query_app_post = array(
				'DELETE'	=> 'post_approval_posts',
				'WHERE'		=> 'id IN ('.implode(', ', array_values($posts_check)).')'
			);
			
			$forum_db->query_build($query_app_post) or error(__FILE__, __LINE__);
			
			$query_app_post = array(
				'SELECT'	=> 'num_replies',
				'FROM'		=> 'post_approval_topics',
				'WHERE'		=> 'id='.$aptid
			);
			
			$count_replies = count($posts_check);
			
			$result_app_post = $forum_db->query_build($query_app_post) or error(__FILE__, __LINE__);
			$num_replies = $forum_db->result($result_app_post) - $count_replies;
			
			$query_app_post = array(
				'SELECT'	=> 'id, posted',
				'FROM'		=> 'post_approval_posts',
				'WHERE'		=> 'topic_id='.$aptid,
				'ORDER BY'	=> 'posted DESC'
			);
			
			$result_app_post = $forum_db->query_build($query_app_post) or error(__FILE__, __LINE__);
			if ($forum_db->num_rows($result_app_post))
			{
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
			
			redirect(forum_link($post_app_url['Section'], $aptid), $lang_app_post['topic redirect']);
		}
	}
	if (isset($_POST['app_sev']))
	{
		if (isset($_POST['sel_posts']))
		{
			$arr_ID_app = array();
			$arr_ID_app = array_keys($_POST['sel_posts']);
			
			$count_arr = count($arr_ID_app);
			$boo = 0;
			if ($count_arr != 0)
			{
				$query_app_post = 
				
				$query = array(
					'SELECT'	=> 'p.id, p.poster, p.poster_id, p.poster_ip, p.poster_email, p.message, p.hide_smilies, p.posted, p.edited, p.edited_by, p.topic_id, t.subject',
					'FROM'		=> 'post_approval_posts AS p',
					'JOINS'		=> array(
						array(
							'INNER JOIN'	=> 'topics AS t',
							'ON'			=> 'p.topic_id=t.id'
						)
					),
					'WHERE'		=> 'p.id IN ('.implode(', ', $arr_ID_app).')'
				);
				$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
				
				if (!$forum_db->num_rows($result))
					message($lang_common['Bad request']);
				
				while ($row = $forum_db->fetch_assoc($result))
				{
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
				$query_app_post = array(
					'DELETE'	=> 'post_approval_posts',
					'WHERE'		=> 'id IN ('.implode(', ', $arr_ID_app).')'
				);
				
				$forum_db->query_build($query_app_post) or error(__FILE__, __LINE__);
			}
			
			redirect(forum_link($post_app_url['Section'], $aptid), $lang_app_post['topic red app']);
		}
	}
	
	if (isset($_POST['app_all']))
	{
		$query_app_post = array(
			'SELECT'	=> 'id',
			'FROM'		=> 'post_approval_posts',
			'WHERE'		=> 'topic_id='.$aptid
		);
		
		$result_app_post = $forum_db->query_build($query_app_post) or error(__FILE__, __LINE__);
		$arr_ID_app = array();
		
		if ($forum_db->num_rows($result_app_post))
		{
			while($row = $forum_db->fetch_assoc($result_app_post))
			{
				$arr_ID_app[] = $row['id'];
			}
		}
		
		$count_arr = count($arr_ID_app);
		
		if ($count_arr != 0)
		{
			$query_app_post = array(
				'SELECT'	=> 'poster, poster_id, poster_ip, poster_email, message, hide_smilies, posted, edited, edited_by, topic_id',
				'FROM'		=> 'post_approval_posts',
				'WHERE'		=> 'id IN ('.implode(', ', $arr_ID_app).')',
				'ORDER BY'	=> 'posted ASC'
			);
			
			$result_app_post = $forum_db->query_build($query_app_post) or error(__FILE__, __LINE__);
			
			$arr_keys_app = array();
			$count_replies = 0;
			$arr_val_app = array();
			
			while($row = $forum_db->fetch_assoc($result_app_post))
			{
				++$count_replies;
				
				$query_app_post = array(
					'INSERT'	=> 'poster, poster_id, poster_ip, message, hide_smilies, posted, topic_id, app_timestamp, app_username',
					'INTO'		=> 'posts',
					'VALUES'	=> '\''.$forum_db->escape($row['poster']).'\', '.$row['poster_id'].', \''.$row['poster_ip'].'\', \''.$forum_db->escape($row['message']).'\', '.$row['hide_smilies'].', '.$row['posted'].', '.$row['topic_id'].', '.time().', '.$forum_user['username']
				);
				
				$forum_db->query_build($query_app_post) or error(__FILE__, __LINE__);
				
				$arr_val_app[$count_replies] = $row;
				$arr_val_app[$count_replies]['id'] = $forum_db->insert_id();
			}
			
			$query_app_post = array(
				'SELECT'	=> 'num_replies',
				'FROM'		=> 'topics',
				'WHERE'		=> 'id='.$aptid
			);
			
			$result_app_post = $forum_db->query_build($query_app_post) or error(__FILE__, __LINE__);
			$num_replies = $forum_db->result($result_app_post) + $count_replies;
			
			$query_app_post = array(
				'UPDATE'	=> 'topics',
				'SET'		=> 'num_replies='.$num_replies.', last_post='.$arr_val_app[$count_replies]['posted'].', last_post_id='.$arr_val_app[$count_replies]['id'],
				'WHERE'		=> 'id='.$aptid
			);
			
			$forum_db->query_build($query_app_post) or error(__FILE__, __LINE__);
			
			$query_app_post = array(
				'DELETE'	=> 'post_approval_posts',
				'WHERE'		=> 'topic_id='.$aptid
			);
			
			$forum_db->query_build($query_app_post) or error(__FILE__, __LINE__);
			
			$query_app_post = array(
				'UPDATE'	=> 'post_approval_topics',
				'SET'		=> 'num_replies=0, last_post=0, last_post_id=0',
				'WHERE'		=> 'id='.$aptid
			);
			
			$forum_db->query_build($query_app_post) or error(__FILE__, __LINE__);
			
			redirect(forum_link($post_app_url['Section'], $aptid), $lang_app_post['topic red app']);
		}
		else
			redirect(forum_link($post_app_url['Section'], $aptid), $lang_app_post['topic red app']);
	}
	
	if ($aptid or isset($appid))
	{
	$forum_page = array();
	
	// Determine on what page the post is located (depending on $forum_user['disp_posts'])
	$query = array(
		'SELECT'	=> 'COUNT(id)',
		'FROM'		=> 'post_approval_posts',
		'WHERE'		=> 'topic_id='.$aptid
	);
	
	$res_count = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	
	$num_posts = $forum_db->result($res_count) ;
	$forum_page['num_pages'] = ceil($num_posts / $forum_user['disp_posts']);
	$forum_page['page'] = (!isset($_GET['page']) || !is_numeric($_GET['page']) || $_GET['page'] <= 1 || $_GET['page'] > $forum_page['num_pages']) ? 1 : $_GET['page'];
	$forum_page['start_from'] = $forum_user['disp_posts'] * ($forum_page['page'] - 1);
	$forum_page['finish_at'] = min(($forum_page['start_from'] + $forum_user['disp_posts']), $num_posts);
	$forum_page['main_info'] = sprintf($lang_common['Page info'], $lang_common['Posts'], $forum_page['start_from'] + 1, $forum_page['finish_at'], $num_posts );

	$query = array(
		'SELECT'	=> 'unp.id, unp.poster_id AS unp_poster_id, unp.topic_id AS unp_topic_id, unp.posted AS unp_posted, unp.message AS unp_message, t.subject AS top_subject, t.poster AS top_poster, t.posted AS top_posted, u.username, u.registered, t.subject, u.email',
		'FROM'		=> 'post_approval_posts AS unp',
		'JOINS'		=> array(
			array(
				'LEFT JOIN'		=> 'topics AS t',
				'ON'			=> 'unp.topic_id = t.id'
			),
			array(
				'LEFT JOIN'		=> 'users AS u',
				'ON'			=> 'unp.poster_id = u.id'
			)
		),
		'LIMIT'	=> $forum_page['start_from'].', '.$forum_user['disp_posts']
	);
	
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	
	if (!$forum_db->num_rows($result))
	{
	
	?>
		
		<div class="frm-info">
			<p><?php echo $lang_app_post['No posts']; ?></p>
		</div>
	<?php
	
	}	
	else
	{
		// Setup breadcrumbs
		$forum_page['crumbs'] = array(
			array($forum_config['o_board_title'], forum_link($forum_url['index'])),
			array($cur_forum['forum_name'], forum_link($forum_url['forum'], array($id, sef_friendly($cur_forum['forum_name']))))
		);
		
		$forum_page['page_post']['paging'] = '<p class="paging"><span class="pages">'.$lang_common['Pages'].'</span> '.paginate($forum_page['num_pages'], $forum_page['page'], forum_link($post_app_url['Page url'], $forum_page['page']), $lang_common['Paging separator']).'</p>';
		
		?>
		
		<div class="main-subhead">
			<h2 class="hn"><span><?php echo $lang_app_post['Unp posts'] ?></span></h2>
		</div>
		<div class="paged-head">
			<?php echo implode("\n\t\t", $forum_page['page_post'])."\n" ?>
		</div>
		<div class="main-head">
			<h2 class="hn"><span><?php echo $forum_page['main_info']; ?></span></h2>
		</div>
		<div class="main-content main-frm">
			<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo forum_link($post_app_url['Section']) ?>">
				<div class="hidden">
					<input type="hidden" name="csrf_token" value="<?php echo generate_form_token(forum_link($post_app_url['Posts section'])) ?>" />
				</div>
				<div class="ct-box warn-box">
					<p class="warn"><?php echo $lang_app_post['warn'] ?></p>
				</div>
		
	<?php
		
		$start = $forum_page['start_from'];
		
		while ($cur_post = $forum_db->fetch_assoc($result))
		{
			$forum_page['user_ident'] = array();
			$forum_page['user_info'] = array();
			$forum_page['message'] = array();
			
			// Generate author identification
			if ($cur_post['unp_poster_id'] > 1 && $forum_config['o_avatars'] == '1' && $forum_user['show_avatars'] != '0')
			{
				$forum_page['avatar_markup'] = generate_avatar_markup($cur_post['unp_poster_id']);

				if (!empty($forum_page['avatar_markup']))
					$forum_page['user_ident']['avatar'] = $forum_page['avatar_markup'];
			}
			
			if ($cur_post['unp_poster_id'] > 1)
			{
				$forum_page['user_ident']['username'] = ($forum_user['g_view_users'] == '1') ? '<strong class="username"><a title="'.sprintf($lang_topic['Go to profile'], forum_htmlencode($cur_post['username'])).'" href="'.forum_link($forum_url['user'], $cur_post['unp_poster_id']).'">'.forum_htmlencode($cur_post['username']).'</a></strong>' : '<strong class="username">'.forum_htmlencode($cur_post['username']).'</strong>';
				$forum_page['user_info']['registered'] = '<li><span><strong>'.$lang_topic['Registered'].'</strong> '.format_time($cur_post['registered'], true).'</span></li>';			
			}
			else
				$forum_page['user_ident']['username'] = '<strong class="username">'.forum_htmlencode($cur_post['username']).'</strong>';

			$forum_page['user_info']['ban'] = '<li><span><a href="'.forum_link($forum_url['admin_bans']).'?add_ban='.$cur_post['unp_poster_id'].'">'.$lang_profile['Ban user'].'</a></span></li>';
			$forum_page['user_info']['email'] = '<li><a href="mailto:'.$cur_post['email'].'"><span>'.$lang_topic['E-mail'].'<span>&#160;'.forum_htmlencode($cur_post['username']).'</span></span></a></li>';
			
			$forum_page['item_ident'] = array(
				'user'	=> '<cite>'.sprintf($lang_topic['Reply title'], forum_htmlencode($cur_post['username'])).'</cite>',
				'date'	=> '<span>'.format_time($cur_post['unp_posted']).'</span>'
			);
			$forum_page['item_select'] = '<div class="checkbox radbox item-select"><label for="fld'.++$start.'"><span class="fld-label">'.$lang_app_post['Sel post'].'</span><input id="fld'.$start.'" type="checkbox" value="1" name="sel_posts['.$cur_post['id'].']"/><strong>'.$start.'</strong></label></div>';
			$forum_page['item_head'] = '<a class="permalink" rel="bookmark" title="'.$lang_topic['Permalink post'].'" href="'.forum_link($post_app_url['Permalink post'], $cur_post['id']).'">'.implode(' ', $forum_page['item_ident']).'</a>';
		
			$forum_page['item_subject'] = $lang_common['Re'].' '.$cur_post['subject'];
			$forum_page['post_options']['remove_post'] = '<a href="'.forum_link($post_app_url['approval'], $cur_post['id']).'">'.$lang_app_post['Approve'].'</a>'; // link for approval
			$forum_page['post_options']['approve_post'] = '<a href="'.forum_link($post_app_url['delete'], $cur_post['id']).'">'.$lang_app_post['Remove'].'</a>'; // link for remove post
			
		?>
			<div class="post odd replypost">
				<div class="postmain">
					<div id="p<?php echo $cur_post['unp_poster_id'] ?>" class="posthead">
						<h3><?php echo $forum_page['item_head'] ?></h3>
					</div>
					<?php echo $forum_page['item_select']."\n"; ?>
					<div class="postbody">
						<div class="user">
							<h4 class="user-ident"><?php echo implode(' ', $forum_page['user_ident']) ?></h4>
							<ul class="user-info">
								<?php echo implode("\n\t\t\t\t\t\t\t", $forum_page['user_info'])."\n" ?>
							</ul>
						</div>
						<div class="post-entry">
							<h4 class="entry-title"><?php echo $forum_page['item_subject'] ?></h4>
								<div class="entry-content">
									<p>
										<?php echo $cur_post['unp_message']."\n" ?>
									</p>
								</div>
						</div>
					</div>
					<div class="postfoot">
						<div class="post-options">
						<?php echo implode(' ', $forum_page['post_options'])."\n" ?>
						</div>
					</div>
				</div>
			</div> 

<?php

		}

?>
				<div class="frm-buttons">
						<span class="submit"><input type="submit" name="app_sev" value="Approve selected" /></span>
						<span class="submit"><input type="submit" name="del_sev" value="Remove selected" /></span>
						<span class="submit"><input type="submit" name="app_all" value="Approve all" /></span>
				</div>

			</form>
		</div>
        
     <?php
	 }
	}
}

?>