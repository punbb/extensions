<?php

if (!defined('FORUM_ROOT'))
	define('FORUM_ROOT', '../../');
	
require FORUM_ROOT.'include/common.php';
require FORUM_ROOT.'include/common_admin.php';

require FORUM_ROOT.'lang/'.$forum_user['language'].'/common.php';
if ($forum_user['g_id'] != FORUM_ADMIN)
	message($lang_common['No permission']);
	
require FORUM_ROOT.'lang/'.$forum_user['language'].'/admin.php';
require FORUM_ROOT.'extensions/pun_approval/lang/'.$forum_user['language'].'/pun_approval.php';
require FORUM_ROOT.'extensions/pun_approval/post_app_url.php';

// Setup breadcrumbs
$forum_page['crumbs'] = array(
	array($forum_config['o_board_title'], forum_link($forum_url['index'])),
	array($lang_admin['Forum administration'], forum_link($forum_url['admin_index'])),
	$lang_app_post['name page']
);

if (isset($_GET['section']) && $_GET['section'] == 'unp_posts')
{
	if (isset($_GET['rem']))
	{
		$rem_id = isset($_GET['rem']) ? intval($_GET['rem']) : 0;
		if ($rem_id < 1)
			message($lang_common['Bad request']);
			
		$query = array(
			'DELETE'	=>	'post_approval_posts',	
			'WHERE'		=>	'id = '.$rem_id
		);	
		$forum_db->query_build($query) or error(__FILE__, __LINE__);		
	}
	else if (isset($_GET['app']))
	{
		$app_id = intval($_GET['app']);
		if ($app_id < 1)
			message($lang_common['Bad request']);
			
		add_message($app_id);		
		$query = array(
			'DELETE'	=>	'post_approval_posts',	
			'WHERE'		=>	'id = '.$app_id
		);
		$forum_db->query_build($query) or error(__FILE__, __LINE__);		
	}
	else if (isset($_POST['sel_posts']) && !isset($_POST['app_all']))	
	{	
		if (@preg_match('/[^0-9,]/', $_POST['sel_posts']))
			message($lang_common['Bad request']);

		$posts = array_keys($_POST['sel_posts']);
		if (isset($_POST['remove_posts']))
		{
			$query = array(
				'DELETE'	=>	'post_approval_posts',	
				'WHERE'		=>	'id IN('.implode(',', $posts).')'
			);	
			$forum_db->query_build($query) or error(__FILE__, __LINE__);
		}
		else if (isset($_POST['approve_posts']))
		{
			for ($app_num = 0; $app_num < count($posts); $app_num++)
				add_message($posts[$app_num]);
							
			$query = array(
				'DELETE'	=>	'post_approval_posts',	
				'WHERE'		=>	'id IN('.implode(',', $posts).')'
			);
			$forum_db->query_build($query) or error(__FILE__, __LINE__);
		}
	}
	else if (isset($_POST['app_all']))
	{
		$query = array(
			'SELECT'	=>	'id',
			'FROM'		=>	'post_approval_posts'			
		);	
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);	
		
		while (list($id) = $forum_db->fetch_row($result))
			add_message($id);
						
		$query = array(
			'DELETE'	=>	'post_approval_posts'
		);	
		$forum_db->query_build($query) or error(__FILE__, __LINE__);
	}
}
else if (isset($_GET['section']) && $_GET['section'] == 'unp_topics')
{

}


define('FORUM_PAGE_SECTION', 'admin-post_approval');
if (isset($_GET['section']) && $_GET['section'] == 'unp_topics')
	define('FORUM_PAGE', 'admin-unapp_topics');
else
	define('FORUM_PAGE', 'admin-unapp_posts');

require FORUM_ROOT.'header.php';

ob_start();
		
?>		
<div id="brd-main" class="main sectioned admin">

<?php echo generate_admin_menu(); ?>

	<div class="main-head">
		<h1><span>{ <?php echo end($forum_page['crumbs']) ?> }</span></h1>
	</div>

	<div class="main-content frm">		
    	<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo forum_link($post_app_url['Posts section']); ?>">
<?php show_unapproved_posts(); ?>
		</form>
	</div>
</div>

<?php

$tpl_app_post = trim(ob_get_contents());
$tpl_main = str_replace('<!-- forum_main -->', $tpl_app_post, $tpl_main);
			
ob_end_clean();
				
require FORUM_ROOT.'footer.php';

?>