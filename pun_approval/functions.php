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
	global $forum_db, $forum_user, $forum_url, $lang_common, $lang_app_post, $forum_config;
	
	require FORUM_ROOT.'lang/'.$forum_user['language'].'/topic.php';
	require FORUM_ROOT.'lang/'.$forum_user['language'].'/profile.php';
	require FORUM_ROOT.'lang/'.$forum_user['language'].'/common.php';
	require FORUM_ROOT.'extensions/pun_approval/post_app_url.php';
	
	$aptid = isset($_GET['aptid']) ? $_GET['aptid'] : 0;
	$pid = isset($_GET['appid']) ? $_GET['appid'] : 0;
	$action = isset($_GET['action']) ? $_GET['action'] : null;
	$del = isset($_GET['del']) ? $_GET['del'] : 0;
	$app = isset($_GET['app']) ? $_GET['app'] : 0;
	$topics = isset($_GET['topics']) ? $_GET['topics'] : 0;
	
	if (($aptid < 0) || ($del < 0) || ($app < 0) || ($pid < 0))
		message($lang_common['Bad request']);
	
	if ($topics)
	{
		// Fetch list of topics
		$query_app_post = array(
			'SELECT'	=> 'c.cat_name, t.id, t.poster, t.subject, t.posted, t.first_post_id, t.last_post, t.last_post_id, t.last_poster, t.num_views, t.num_replies, t.closed, t.sticky, t.moved_to',
			'FROM'		=> 'post_approval_topics AS t',
			'JOINS'		=> array(
				array(
					'INNER JOIN'	=> 'forums AS f',
					'ON'			=> 't.forum_id=f.id'
				),
				array(
					'INNER JOIN'	=> 'categories AS c',
					'ON'			=> 'f.cat_id=c.id'
				),
			),
			'ORDER BY'	=> 't.last_post DESC'
		);
		
		$result_app_topic = $forum_db->query_build($query_app_post) or error(__FILE__, __LINE__);
		
		?>
			
				<div class="main-head">
				<?php

					if (!empty($forum_page['main_head_options']))
						echo "\n\t\t".'<p class="options">'.implode(' ', $forum_page['main_head_options']).'</p>';

				?>
		<h2 class="hn"><span><?php echo $forum_page['items_info'] ?></span></h2>
	</div>
	<div class="main-subhead">
		<p class="item-summary<?php echo ($forum_config['o_topic_views'] == '1') ? ' forum-views' : ' forum-noview' ?>"><span><?php printf($lang_forum['Forum subtitle'], implode(' ', $forum_page['item_header']['subject']), implode(', ', $forum_page['item_header']['info'])) ?></span></p>
	</div>
	<div id="forum<?php echo $id ?>" class="main-content main-forum<?php echo ($forum_config['o_topic_views'] == '1') ? ' forum-views' : ' forum-noview' ?>">
			
			<div id="brd-main" class="main sectioned admin">
				<div class="main-head">
					<h1><span>{ <?php echo end($forum_page['crumbs']) ?> }</span></h1>
				</div>
				
				<div class="main-content frm">
					<div class="frm-head">
						<h2><span><?php echo $lang_app_post['name page'] ?></span></h2>
					</div>
					<div class="main-content frm">
							
							<?php
								
								if ($forum_db->num_rows($result_app_topic))
								{
									
									?>
									
										<div id="forum<?php echo $id ?>" class="main-content forum">
											<table cellspacing="0" >
												<thead>
													<tr>
														<th class="tcl" scope="col"><?php echo $lang_app_post['name 1 col'] ?></th>
														<th class="tc2" scope="col"><?php echo $lang_app_post['name 2 col'] ?></th>
														<th class="tc3" scope="col"><?php echo $lang_app_post['name 3 col'] ?></th>
														<th class="tcr" scope="col"><?php echo $lang_app_post['name 4 col'] ?></th>
													</tr>
												</thead>
												<tbody class="statused">
												
												<?php
													
													while ($cur_topic = $forum_db->fetch_assoc($result_app_topic))
													{
														++$forum_page['item_count'];
														
														// Start from scratch
														$forum_page['item_subject'] = $forum_page['item_status'] = $forum_page['item_last_post'] = $forum_page['item_alt_message'] = $forum_page['item_nav'] = array();
														$forum_page['item_indicator'] = '';
														$forum_page['item_alt_message'][] = $lang_topic['Topic'].' '.($forum_page['item_count']);

														if ($forum_config['o_censoring'] == '1')
															$cur_topic['subject'] = censor_words($cur_topic['subject']);

														if ($cur_topic['moved_to'] != null)
														{
															$forum_page['item_status'][] = 'moved';
															$forum_page['item_last_post'][] = $forum_page['item_alt_message'][] = $lang_app_post['Moved'];
															$forum_page['item_subject'][] = '<a href="'.forum_link($post_app_url['topic'], array($cur_topic['moved_to'], sef_friendly($cur_topic['subject']))).'">'.forum_htmlencode($cur_topic['subject']).'</a>';
															$forum_page['item_subject'][] = '<span class="byuser">'.sprintf($lang_common['By user'], forum_htmlencode($cur_topic['poster'])).'</span>';
															$cur_topic['num_replies'] = $cur_topic['num_views'] = ' - ';
														}
														else
														{
															// Should we display the dot or not? :)
															if (!$forum_user['is_guest'] && $forum_config['o_show_dot'] == '1' && $cur_topic['has_posted'] == $forum_user['id'])
															{
																$forum_page['item_indicator'] = $lang_app_post['You posted indicator'];
																$forum_page['item_status'][] = 'posted';
																$forum_page['item_alt_message'][] = $lang_app_post['You posted'];
															}

															if ($cur_topic['closed'] == '1')
															{
																$forum_page['item_subject'][] = $lang_common['Closed'];
																$forum_page['item_status'][] = 'closed';
															}

															$forum_page['item_subject'][] = '<a href="'.forum_link($post_app_url['topic'], array($cur_topic['id'], sef_friendly($cur_topic['subject']))).'">'.forum_htmlencode($cur_topic['subject']).'</a>';

															$forum_page['item_pages'] = ceil(($cur_topic['num_replies'] + 1) / $forum_user['disp_posts']);

															if ($forum_page['item_pages'] > 1)
																$forum_page['item_nav'][] = paginate($forum_page['item_pages'], -1, $post_app_url['topic'], $lang_common['Page separator'], array($cur_topic['id'], sef_friendly($cur_topic['subject'])));

															if (!empty($forum_page['item_nav']))
																$forum_page['item_subject'][] = '<span class="topic-nav">[&#160;'.implode('&#160;&#160;', $forum_page['item_nav']).'&#160;]</span>';

															$forum_page['item_subject'][] = '<span class="byuser">'.sprintf($lang_common['By user'], forum_htmlencode($cur_topic['poster'])).'</span>';
															
															if ($cur_topic['last_post'] != 0)
																$forum_page['item_last_post'][] = '<a href="'.forum_link($post_app_url['post'], $cur_topic['last_post_id']).'"><span>'.format_time($cur_topic['last_post']).'</span></a><span><em>'.$cur_topic['last_poster'].'</em></span>';
															else
																$forum_page['item_last_post'][] = $lang_app_post['no topic'];
														}

														$forum_page['item_style'] = (($forum_page['item_count'] % 2 != 0) ? 'odd' : 'even').' '.implode(' ', $forum_page['item_status']);
														$forum_page['item_indicator'] = '<span class="status '.implode(' ', $forum_page['item_status']).'" title="'.implode(' - ', $forum_page['item_alt_message']).'"><img src="'.$base_url.'/style/'.$forum_user['style'].'/status.png" alt="'.implode(' - ', $forum_page['item_alt_message']).'" />'.$forum_page['item_indicator'].'</span>';
													
													?>
													
														<tr class="<?php echo $forum_page['item_style'] ?>">
															<td class="tcl"><?php echo $forum_page['item_indicator'].' '.implode(' ', $forum_page['item_subject']) ?></td>
															<td class="tc2"><?php echo $cur_topic['cat_name'] ?></td>
															<td class="tc3"><?php echo $cur_topic['num_replies'] ?></td>
															<td class="tcr"><?php echo implode(' ', $forum_page['item_last_post']) ?></td>
														</tr>
													
													<?php
													
													}
													
													?>
												
												</tbody>
											</table>
										</div>
									
									<?php
									
								}
								else
								{
									$result_app_topic_app_post = $forum_db->query_build($query_app_post) or error(__FILE__, __LINE__);
									
									if ($forum_db->num_rows($result_app_topic_app_post))
									{
									
										?>
											
											<div class="frm-info">
												<p><?php echo $lang_app_post['no posts'] ?></p>
											</div>
											
										<?php
										
									}
								}
								
							?>
					</div>
				</div>
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
	
	$forum_page = array();
	
	// Determine on what page the post is located (depending on $forum_user['disp_posts'])
	$query = array(
		'SELECT'	=> 'COUNT(id)',
		'FROM'		=> 'post_approval_posts'
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
		
		
		$forum_page['page_post']['paging'] = '<p class="paging"><span class="pages">'.$lang_common['Pages'].'</span> '.paginate($forum_page['num_pages'], $forum_page['page'], forum_link($post_app_url['Page url'], $forum_page['page']), $lang_common['Paging separator']).'</p>';
		
		?>
		
		<div class="main-subhead">
			<h2 class="hn"><span><?php echo $lang_app_post['Unp posts'] ?><a href="<?php echo forum_link($post_app_url['Topics section'])?>">Topics</a></span></h2>
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

?>