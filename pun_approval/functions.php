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
	add_post($post_info, $new_pid);
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
	
	if (($aptid < 0) || ($del < 0) || ($app < 0) || ($pid < 0))
		message($lang_common['Bad request']);
	
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
			'INSERT'	=> 'poster, poster_id, poster_ip, message, hide_smilies, posted, topic_id, app_timestamp, app_username',
			'INTO'		=> 'posts',
			'VALUES'	=> '\''.$forum_db->escape($row['poster']).'\', '.$row['poster_id'].', \''.$row['poster_ip'].'\', \''.$forum_db->escape($row['message']).'\', '.$row['hide_smilies'].', '.$row['posted'].', '.$row['topic_id'].', '.time().', \''.$forum_user['username'].'\''
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
			$arr_ID_app = $_POST['sel_posts'];
			
			$count_arr = count($arr_ID_app);
			
			if ($count_arr != 0)
			{
				$query_app_post = array(
					'SELECT'	=> 'id, poster, poster_id, poster_ip, poster_email, message, hide_smilies, posted, edited, edited_by, topic_id',
					'FROM'		=> 'post_approval_posts',
					'WHERE'		=> 'id IN ('.implode(', ', $arr_ID_app).')',
					'ORDER BY'	=> 'posted ASC'
				);
				
				$result_app_post = $forum_db->query_build($query_app_post) or error(__FILE__, __LINE__);
				
				$arr_keys_app = array();
				$arr_val_app = array();
				$count_replies = 0;
				
				while($row = $forum_db->fetch_assoc($result_app_post))
				{
					$count_replies++;
					
					$query_app_post = array(
						'INSERT'	=> 'poster, poster_id, poster_ip, message, hide_smilies, posted, topic_id, app_timestamp, app_user_name',
						'INTO'		=> 'posts',
						'VALUES'	=> '\''.$forum_db->escape($row['poster']).'\', '.$row['poster_id'].', \''.$row['poster_ip'].'\', \''.$forum_db->escape($row['message']).'\', '.$row['hide_smilies'].', '.$row['posted'].', '.$row['topic_id'].', '.time().', '.$forum_user['username']
					);
					
					$arr_val_app[$count_replies] = $row;
					$arr_val_app[$count_replies]['id'] = $forum_db->insert_id();
					
					$forum_db->query_build($query_app_post) or error(__FILE__, __LINE__);
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
				
				//$forum_db->query_build($query_app_post) or error(__FILE__, __LINE__);
				echo '<pre>';
				var_dump($query_app_post);
				echo '</pre>';
				
				$query_app_post = array(
					'DELETE'	=> 'post_approval_posts',
					'WHERE'		=> 'id IN ('.implode(', ', $arr_ID_app).')'
				);
				
				$forum_db->query_build($query_app_post) or error(__FILE__, __LINE__);
				
				$query_app_post = array(
					'SELECT'	=> 'num_replies',
					'FROM'		=> 'post_approval_topics',
					'WHERE'		=> 'id='.$aptid
				);
				
				
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
				
				redirect(forum_link($post_app_url['Section'], $aptid), $lang_app_post['topic red app']);
			}
			else
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
					'INSERT'	=> 'poster, poster_id, poster_ip, message, hide_smilies, posted, topic_id, app_timestamp, app_user_name',
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
					<input type="hidden" name="csrf_token" value="<?php echo forum_link($post_app_url['Posts section']); ?>" />
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
			<div class="paged-foot">
				<p class="submitting">
					<span class="submit"><input type="submit" name="app_sev" value="Approve selected" /></span>
					<span class="submit"><input type="submit" name="del_sev" value="Remove selected" /></span>
					<span class="submit"><input type="submit" name="app_all" value="Approve all" /></span>
				</p>
				<p class="paging"> <?php echo $forum_page['page_post']['paging']; ?></p>
			</div>

			</form>
		</div>
        
     <?php
	 
	}	
}

?>