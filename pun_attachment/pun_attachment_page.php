<?php

/**
 * Attachment page
 *
 * @copyright Copyright (C) 2008-2012 PunBB, partially based on Attachment Mod by Frank Hagstrom
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_attachment
 */

if (!defined('FORUM')) die();

$errors = array();
$pun_attach_rename_error = 0;
$pun_attach_attach_error = 0;

if (isset($_GET['id']))
{
	$id = intval($_GET['id']);
	if (isset($_POST['pun_attach_detach']))
	{
		if (!isset($_POST['csrf_token']) && (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== generate_form_token('delete'.$forum_user['id'])))
			csrf_confirm_form();

		$query = array(
			'UPDATE'	=> 'attach_files',
			'SET'		=> 'post_id=0, topic_id=0, owner_id=0',
			'WHERE'		=> 'id = '.$id
		);
		$result = $forum_db->query_build($query) or error(__FILE__,__LINE__);
	}

	if (isset($_POST['pun_attach_rename']))
	{
		if (!isset($_POST['csrf_token']) && (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== generate_form_token('delete'.$forum_user['id'])))
			csrf_confirm_form();

		$new_name = forum_htmlencode($_POST['pun_attach_new_name']);
		if (empty($new_name))
		{
			$pun_attach_rename_error = 1;
			$errors[] = $lang_attach['Too short filename'];
		}

		if (empty($errors))
		{
			$query = array(
				'SELECT'	=> 'filename',
				'FROM'		=> 'attach_files',
				'WHERE'		=> 'id='.$id
			);
			$result = $forum_db->query_build($query) or error(__FILE__,__LINE__);
			$pun_attach_filename = $forum_db->fetch_assoc($result);

			if (!$pun_attach_filename)
				message($lang_common['Bad request']);

			preg_match('/\.[0-9a-zA-z]{1,}$/', $pun_attach_filename['filename'], $pun_attach_filename_ext);

			$new_name = $new_name.$pun_attach_filename_ext[0];
			$query = array(
				'UPDATE'	=> 'attach_files',
				'SET'		=> 'filename=\''.$forum_db->escape($new_name).'\'',
				'WHERE'		=> 'id='.$id
			);
			$forum_db->query_build($query) or error(__FILE__,__LINE__);
		}
	}

	if (isset($_POST['pun_attach_delete']))
	{
		if (!isset($_POST['csrf_token']) && (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== generate_form_token('delete'.$forum_user['id'])))
			csrf_confirm_form();

		$attach_query = array(
			'SELECT'	=>	'file_path',
			'FROM'		=>	'attach_files',
			'WHERE'		=>	'id = '.$id
		);
		$attach_result = $forum_db->query_build($attach_query) or error(__FILE__, __LINE__);
		$del_attach_info = $forum_db->fetch_assoc($attach_result);

		if (!$del_attach_info)
			message($lang_common['Bad request']);

		unlink(FORUM_ROOT.$forum_config['attach_basefolder'].$del_attach_info['file_path']);

		$attach_query = array(
			'DELETE'	=>	'attach_files',
			'WHERE'		=>	'id = '.$id
		);
		$forum_db->query_build($attach_query) or error(__FILE__, __LINE__);

		redirect(forum_link($attach_url['admin_attachment_manage']), $lang_attach['Attachment delete']);
	}

	if (isset($_POST['pun_attach_attach']))
	{
		$post_id = intval($_POST['pun_attach_new_post_id']);

		if ($post_id <= 0)
		{
			$pun_attach_attach_error = 1;
			$errors[] = $lang_attach['Empty post id'];
		}

		if (empty($errors))
		{
			$query = array(
				'SELECT'	=> 't.id as topic_id, p.id as post_id',
				'FROM'		=> 'posts as p',
				'JOINS'		=> array(
					array(
						'LEFT JOIN'	=> 'topics t',
						'ON'		=> 'p.topic_id=t.id'
					)
				),
				'WHERE'		=> 'p.id='.$post_id
			);
			$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
			$pun_attach_topic_id = $forum_db->fetch_assoc($result);

			if (!$pun_attach_topic_id)
			{
				$pun_attach_attach_error = 1;
				$errors[] = $lang_attach['Wrong post id'];
			}

			if (empty($errors))
			{
				$query = array(
					'UPDATE'	=> 'attach_files',
					'SET'		=> 'topic_id = '.$pun_attach_topic_id['topic_id'].', post_id = '.$pun_attach_topic_id['post_id'],
					'WHERE'		=> 'id = '.$id
				);
				$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
			}
		}
	}

	$query = array(
		'SELECT'	=> 'af.*, u.username, u.id, t.subject, t.id AS topic_id',
		'FROM'		=> 'attach_files AS af',
		'JOINS'		=> array(
			array(
				'LEFT JOIN'	=> 'topics t',
				'ON'	=> 'af.topic_id=t.id'
			),
			array(
				'LEFT JOIN'	=> 'users u',
				'ON'	=> 'u.id=af.owner_id'
			)
		),
		'WHERE'		=> 'af.id='.$id
	);

	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$pun_current_attach = $forum_db->fetch_assoc($result);

	if (!$pun_current_attach)
		message($lang_common['Bad request']);


	$pun_attach_frm_buttons = array();
	$pun_attach_frm_buttons[] = '<span class="submit"><input type="submit" name="pun_attach_rename" value="'.$lang_attach['Rename button'].'" /></span>';
	$pun_attach_frm_buttons[] = '<span class="submit"><input type="submit" name="pun_attach_delete" value="'.$lang_attach['Delete button'].'" /></span>';

	if ($pun_current_attach['post_id'] == '0')
		$pun_attach_frm_buttons[] = '<span class="submit"><input type="submit" name="pun_attach_attach" value="'.$lang_attach['Attach button'].'" /></span>';
	else
		$pun_attach_frm_buttons[] = '<span class="submit"><input type="submit" name="pun_attach_detach" value="'.$lang_attach['Detach button'].'" /></span>';

	// Setup the form
	$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;

	// Setup breadcrumbs
	$forum_page['crumbs'] = array(
		array($forum_config['o_board_title'], forum_link($forum_url['index'])),
		array($lang_admin_common['Forum administration'], forum_link($forum_url['admin_index'])),
		array($lang_attach['Manage attahcments'], forum_link($attach_url['admin_attachment_manage'])),
		array(sprintf($lang_attach['Manage id'], $pun_current_attach['filename']), forum_link($attach_url['admin_attachment_edit'], $id))
	);

	define('FORUM_PAGE_SECTION', 'management');
	define('FORUM_PAGE', 'admin-attachment-manage');
	require FORUM_ROOT.'header.php';

	// START SUBST - <!-- forum_main -->
	ob_start();

?>

	<div class="main-content main-frm">
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo forum_link($attach_url['admin_attachment_edit'], array($id)) ?>">
			<div class="hidden">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token(forum_link($attach_url['admin_attachment_edit'], array($id))) ?>" />
			</div>
			<div class="content-head">
				<h2 class="hn"><span><?php echo sprintf($lang_attach['Attachment page head'], $pun_current_attach['filename']) ?></span></h2>
			</div>

<?php

	if (!empty($errors))
	{
		$forum_page['errors'] = array();
		foreach ($errors as $cur_error)
			$forum_page['errors'][] = '<li class="warn"><span>'.$cur_error.'</span></li>';

?>
		<div class="ct-box error-box">
			<h2 class="warn hn"><?php echo ($pun_attach_attach_error == 1 ? $lang_attach['Attach error'] : $lang_attach['Rename error']) ?></h2>
			<ul class="error-list">
				<?php echo implode("\n\t\t\t\t", $forum_page['errors'])."\n" ?>
			</ul>
		</div>
<?php

	}

?>

			<div class="ct-group">
				<table cellspacing="0">
					<thead>
						<tr>
							<th class="tc4" scope="col"><?php echo $lang_attach['Filename'] ?></th>
							<th class="tc1" scope="col"><?php echo $lang_attach['Filesize'] ?></th>
							<th class="tc2" scope="col"><?php echo $lang_attach['Owner'] ?></th>
							<th class="tc3" scope="col"><?php echo $lang_attach['Uploaded date'] ?></th>
							<th class="tc4" scope="col"><?php echo $lang_attach['MIME-type'] ?></th>
							<th class="tc5" scope="col"><?php echo $lang_attach['Topic'] ?></th>
							<th class="tc6" scope="col"><?php echo $lang_attach['Post id'] ?></th>
							<th class="tc7" scope="col"><?php echo $lang_attach['Downloads'] ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td class="tc0" scope="col"><?php echo forum_htmlencode($pun_current_attach['filename']) ?></td>
							<td class="tc1" scope="col"><?php echo format_size($pun_current_attach['size']) ?></td>
							<td class="tc2" scope="col"><?php echo forum_htmlencode($pun_current_attach['username']) ?></td>
							<td class="tc3" scope="col"><?php echo format_time($pun_current_attach['uploaded_at']) ?></td>
							<td class="tc4" scope="col"><?php echo $pun_current_attach['file_mime_type'] ?></td>
<?php if ($pun_current_attach['topic_id'] != '0'): ?>
							<td class="tc5" scope="col"><a href="<?php echo forum_link($forum_url['topic'], array($pun_current_attach['topic_id'])) ?>"><?php echo forum_htmlencode($pun_current_attach['subject']) ?></a></td>
<?php else: ?>
							<td class="tc5" scope="col"><strong><?php echo $lang_attach['None'] ?></strong></td>
<?php endif; ?>
<?php if ($pun_current_attach['post_id'] != '0'): ?>
							<td class="tc6" scope="col"><a href="<?php echo forum_link($forum_url['post'], array($pun_current_attach['post_id'])) ?>"><?php echo $pun_current_attach['post_id'] ?></a></td>
<?php else: ?>
							<td class="tc6" scope="col"><strong><?php echo $lang_attach['None'] ?></strong></td>
<?php endif; ?>
							<td class="tc7" scope="col"><?php echo $pun_current_attach['download_counter'] ?></td>
						</tr>
					</tbody>
				</table>
			</div>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>">
							<span><?php echo $lang_attach['New name'] ?></span>
						</label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="pun_attach_new_name" size="10" maxlength="15" /></span><br />
					</div>
				</div>
<?php if ($pun_current_attach['post_id'] == '0'): ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>">
							<span><?php echo $lang_attach['Post id'] ?></span>
						</label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="pun_attach_new_post_id" size="10" maxlength="15" /></span><br />
					</div>
				</div>
<?php endif; ?>
			</fieldset>
			<div class="frm-buttons">
				<?php echo implode('', $pun_attach_frm_buttons) ?>
			</div>
		</form>
	</div>

<?php

		$tpl_temp = trim(ob_get_contents());
		$tpl_main = str_replace('<!-- forum_main -->', $tpl_temp, $tpl_main);
		ob_end_clean();
		// END SUBST - <!-- forum_main -->

		require FORUM_ROOT.'footer.php';
	}

?>