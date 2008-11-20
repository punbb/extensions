<?php
function add_message( $app_id )
{
	global $forum_db;

	$query = array(
		'SELECT'	=>	'*',
		'FROM'		=>	'post_approval_posts',
		'WHERE'		=>	'id = '.$app_id		
	);
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		
	if (!$forum_db->num_rows($result))
		message($lang_common['Bad request']);
			
	$row = $forum_db->fetch_assoc($result) or error(__FILE__, __LINE__);		
	$post_info = array(
		'is_guest'		=>	($row['id'] == 1) ? (true) : (false),
		'poster'		=>	$row['poster'],
		'poster_id'		=>	$row['poster_id'],
		'poster_email'	=>	$row['poster_email'],
		'message'		=>	$row['message'],
		'hide_smilies'	=>	$row['hide_smilies'],
		'posted'		=>	$row['posted'],
		'topic_id'		=>	$row['topic_id'],
		'subscr_action'	=> 0			
	);
	$query = array(
		'SELECT'	=>	'forum_id',
		'FROM'		=>	'topics',
		'WHERE'		=>	'id = '.$row['topic_id']		
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
	
	$forum_page = array();
	
	// Determine on what page the post is located (depending on $forum_user['disp_posts'])
	$query = array(
		'SELECT'	=> 'COUNT(id)',
		'FROM'		=> 'post_approval_posts'
	);		
	$res_count = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		
	$num_posts = $forum_db->result($res_count) ;
	$forum_page['num_pages'] = ceil($num_posts / $forum_user['disp_posts']);
	$forum_page['page'] = (!isset($_GET['p']) || !is_numeric($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $forum_page['num_pages']) ? 1 : $_GET['p'];
	$forum_page['start_from'] = $forum_user['disp_posts'] * ($forum_page['page'] - 1);	
	$forum_page['finish_at'] = min(($forum_page['start_from'] + $forum_user['disp_posts']), $num_posts);
	$forum_page['main_info'] = sprintf($lang_common['Paged info'], $lang_common['Posts'], $forum_page['start_from'] + 1, $forum_page['finish_at'], $num_posts );

	$query = array(	
		'SELECT'	=>	'unp.id, unp.poster_id AS unp_poster_id, unp.topic_id AS unp_topic_id, unp.posted AS unp_posted, unp.message AS unp_message, t.subject AS top_subject, t.poster AS top_poster, t.posted AS top_posted, u.username, u.registered, t.subject, u.email',
		'FROM'	=>	'post_approval_posts AS unp',
		'JOINS'	=> array(
			array(
				'LEFT JOIN'	=>	'topics AS t',
				'ON'	=>	'unp.topic_id = t.id'
			),
			array(
				'LEFT JOIN'		=>	'users AS u',
				'ON'	=>	'unp.poster_id = u.id'				
			)
		),
		'LIMIT'	=>	$forum_page['start_from'].', '.$forum_user['disp_posts']
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
	
	?>
    	<div class="hidden">
			<input type="hidden" name="csrf_token" value="<?php echo forum_link($post_app_url['Posts section']); ?>" />
		</div>
    	<div class="main-content topic">
     <?php
	 
		$forum_page['page_post']['paging'] = '<p class="paging"><span class="pages">'.$lang_common['Pages'].'</span> '.paginate($forum_page['num_pages'], $forum_page['page'], $post_app_url['Page url'], $lang_common['Paging separator']).'</p>';
		
		?>        
		<div class="paged-head">
			<?php echo implode("\n\t\t", $forum_page['page_post'])."\n" ?>
		</div>        
        <div class="main-head">
			<h2>
				<span>	
					<?php echo $forum_page['main_info']; ?>
				</span>
			</h2>
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
			$forum_page['user_info']['email'] = '<li><a href="mailto:'.$cur_post['email'].'"><span>'.$lang_common['E-mail'].'<span>&#160;'.forum_htmlencode($cur_post['username']).'</span></span></a></li>';
			
			$forum_page['item_ident'] = array(						
				'user'	=> '<cite>'.sprintf($lang_topic['Reply by'], forum_htmlencode($cur_post['username'])).'</cite>',
				'date'	=> '<span>'.format_time($cur_post['unp_posted']).'</span>'
			);
			$forum_page['item_select'] = '<div class="checkbox radbox item-select"><label for="fld'.++$start.'"><span class="fld-label">'.$lang_app_post['Sel post'].'</span><input id="fld'.$start.'" type="checkbox" value="1" name="sel_posts['.$cur_post['id'].']"/><strong>'.$start.'</strong></label></div>';
			$forum_page['item_head'] = '<a class="permalink" rel="bookmark" title="'.$lang_topic['Permalink post'].'" href="'.forum_link($post_app_url['Permalink'], $cur_post['id']).'">'.implode(' ', $forum_page['item_ident']).'</a>';
		
			$forum_page['item_subject'] = $lang_common['Re'].' '.$cur_post['subject'];						
			$forum_page['post_options']['remove_post'] = '<a href="'.forum_link($post_app_url['App link'], $cur_post['id']).'">'.$lang_app_post['Approve'].'</a>';
			$forum_page['post_options']['approve_post'] = '<a href="'.forum_link($post_app_url['Rem link'], $cur_post['id']).'">'.$lang_app_post['Remove'].'</a>';			
			
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
		<p class="submitting"><span class="submit"><input type="submit" name="approve_posts" value="Approve selected" /></span>
			<span class="submit"><input type="submit" name="remove_posts" value="Remove selected" /></span>
			<span class="submit"><input type="submit" name="app_all" value="Approve all" /></span>
		</p>
		<p class="paging"> <?php echo $forum_page['page_post']['paging']; ?></p>
	</div>


		</div>
        
     <?php
	 
	}	
}

?>