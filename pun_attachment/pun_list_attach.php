<?php

/**
 * List of the existing attachments
 *
 * @copyright Copyright (C) 2008-2012 PunBB, partially based on Attachment Mod by Frank Hagstrom
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_attachment
 */

if (!defined('FORUM')) die();

if (file_exists($ext_info['path'].'/lang/'.$forum_user['language'].'/'.$ext_info['id'].'.php'))
	require $ext_info['path'].'/lang/'.$forum_user['language'].'/'.$ext_info['id'].'.php';
else
	require $ext_info['path'].'/lang/English/'.$ext_info['id'].'.php';

if (isset($_GET['id']))
	require $ext_info['path'].'/pun_attachment_page.php';

if (isset($_POST['form']))
	$form = array_map('trim', $_POST['form']);

$form['order'] = isset($form['order']) ? $form['order'] : 'id';
$form['sort_dir'] = isset($form['sort_dir']) ? $form['sort_dir'] : 'ASC';
$form['size_start'] = isset($form['size_start']) ? (($form['size_end']!='' ? ($form['size_start'] < $form['size_end']) : false) ? intval($form['size_start']) : '0') : '0';
$form['size_end'] = isset($form['size_end']) ? intval($form['size_end']) : '';

$start = (isset($form['start']) && intval($form['start']) > 0) ? intval($form['start']) : '1';
$number = (isset($form['number']) && intval($form['number']) > 0) ? intval($form['number']) : '50';


if (isset($_POST['apply']))
{
	$show_orphans = 0;

	if (isset($form['orphans']) && ($form['orphans'] == '1'))
		$show_orphans = 1;
}

// Form the list of all attachments
$query = array(
	'SELECT'	=> 'af.*, u.username, t.subject',
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
	'WHERE'		=> 'af.size>'.$form['size_start'].($form['size_end']!='' ? ' AND af.size<'.$form['size_end'] : ''),
	'ORDER BY'	=> $form['order'].' '.$form['sort_dir'],
	'LIMIT'		=> ($start-1).', '.$number
);

if (isset($show_orphans) && ($show_orphans == '1'))
{
	$query['WHERE'] = 'af.owner_id=0 AND af.topic_id=0 AND af.post_id=0 AND af.size>'.$form['size_start'].($form['size_end']!='' ? ' AND af.size<'.$form['size_end'] : '');
	unset ($form['topic']);
}

if ((isset($form['topic']) && ($form['topic'] != '0')) && (!isset($show_orphans) || $show_orphans == '0'))
{
	$query['WHERE'] = 'u.id=af.owner_id AND af.topic_id=t.id AND t.subject=\''.$forum_db->escape($form['topic']).'\' AND af.size>'.$form['size_start'].($form['size_end']!='' ? ' AND af.size<'.$form['size_end'] : '');
}

if (isset($form['owner_id']) && ($form['owner_id'] != '0'))
{
	$query['WHERE'] .= ' AND af.owner_id='.intval($form['owner_id']);
}
$result = $forum_db->query_build($query) or error(__FILE__,__LINE__);

$attachments = array();
while ($cur_attach = $forum_db->fetch_assoc($result))
{
	$attachments[$cur_attach['id']] = array();
	$attachments[$cur_attach['id']]['owner_id'] = $cur_attach['owner_id'];
	$attachments[$cur_attach['id']]['username'] = $cur_attach['username'];
	$attachments[$cur_attach['id']]['post_id'] = $cur_attach['post_id'];
	$attachments[$cur_attach['id']]['filename'] = $cur_attach['filename'];
	$attachments[$cur_attach['id']]['file_ext'] = $cur_attach['file_ext'];
	$attachments[$cur_attach['id']]['file_mime_type'] = $cur_attach['file_mime_type'];
	$attachments[$cur_attach['id']]['file_path'] = $cur_attach['file_path'];
	$attachments[$cur_attach['id']]['size'] = $cur_attach['size'];
	$attachments[$cur_attach['id']]['download_counter'] = $cur_attach['download_counter'];
	$attachments[$cur_attach['id']]['uploaded_at'] = $cur_attach['uploaded_at'];
	$attachments[$cur_attach['id']]['topic_id'] = $cur_attach['topic_id'];
	$attachments[$cur_attach['id']]['subject'] = $cur_attach['subject'];
}


$attach_owner = array();
$attach_topic = array();

if (!empty($attachments))
{
	foreach ($attachments as $key => $value)
	{
		$attach_owner[$attachments[$key]['owner_id']] = $attachments[$key]['username'];
		$attach_topic[$attachments[$key]['topic_id']] = $attachments[$key]['subject'];
	}

	$attach_topic = array_unique($attach_topic);
	$attach_owner = array_unique($attach_owner);
}


$query = array(
	'SELECT'	=> 'af.id, af.owner_id, p.topic_id, p.poster, t.subject',
	'FROM'		=> 'attach_files as af',
	'JOINS'		=> array(
		array(
			'JOIN'	=> 'posts p',
			'ON'	=> 'af.post_id=p.id'
		),
		array(
			'JOIN'	=> 'topics t',
			'ON'	=> 't.id = p.topic_id'
		)
	),
	'ORDER BY'	=> 'af.'.$form['order'].' '.$form['sort_dir'],
	'LIMIT'		=> ($start-1).', '.$number
);

$result_add = $forum_db->query_build($query) or error(__FILE__, __LINE__);

while($cur_record = $forum_db->fetch_assoc($result_add))
{
	$owner_name[$cur_record['owner_id']] = $cur_record['poster'];
	$topic[$cur_record['subject']] = $cur_record['subject'];
}

// Setup the form
$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;

// Setup breadcrumbs
$forum_page['crumbs'] = array(
	array($forum_config['o_board_title'], forum_link($forum_url['index'])),
	array($lang_admin_common['Forum administration'], forum_link($forum_url['admin_index'])),
	array($lang_admin_common['Management'], forum_link($forum_url['admin_reports'])),
	array($lang_attach['Manage attahcments'], forum_link($attach_url['admin_attachment_manage']))
);

define('FORUM_PAGE_SECTION', 'management');
define('FORUM_PAGE', 'admin-attachment-manage');
require FORUM_ROOT.'header.php';

// START SUBST - <!-- forum_main -->
ob_start();

?>

	<div class="main-content main-frm">
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo forum_link($attach_url['admin_attachment_manage']) ?>">
			<div class="hidden">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token(forum_link($attach_url['admin_attachment_manage'])) ?>" />
			</div>
			<div class="content-head">
				<h2 class="hn"><span><?php echo $lang_attach['Attachments'] ?></span></h2>
			</div>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>">
							<span><?php echo $lang_attach['Start at'] ?></span>
						</label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[start]" value="<?php echo $start ?>" size="10" maxlength="15" /></span>
					</div>
				</div>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>">
							<span><?php echo $lang_attach['Number to show'] ?></span>
						</label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[number]" value="<?php echo $number ?>" size="10" maxlength="15" /></span><br />
					</div>
				</div>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>">
							<span><?php echo $lang_attach['Filesize'] ?></span>
						</label><br />
						<span class="fld-input">
							<input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[size_start]" value="<?php echo (isset($form['size_start']) ? intval($form['size_start']) : '') ?>" size="10" maxlength="15" />
							<?php echo '&nbsp;'.$lang_attach['to'].'&nbsp;' ?>
							<input type="text" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[size_end]" value="<?php echo (isset($form['size_end']) ? intval($form['size_end']) : '') ?>" size="10" maxlength="15" />
						</span>
					</div>
				</div>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>">
							<span><?php echo $lang_attach['Owner'] ?></span>
						</label><br />
						<span class="fld-input">
							<select id="fld<?php echo $forum_page['fld_count'] ?>" name="form[owner_id]">
								<option selected="selected" value="0">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</option>
<?php

							for ($i=0; $i<count($owner_name); $i++)
							{
								$key = array_keys($owner_name);
								echo '<option value="'.$key[$i].'"'.((isset($form['owner_id']) && (intval($form['owner_id']) == $key[$i])) ? ' selected="selected"' : '').'>'.forum_htmlencode($owner_name[$key[$i]]).'</option>';
							}

?>
							</select>
						</span>
					</div>
				</div>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>">
							<span><?php echo $lang_attach['Topic'] ?></span>
						</label><br />
						<span class="fld-input">
							<select id="fld<?php echo $forum_page['fld_count'] ?>" name="form[topic]">
								<option selected="selected" value="0">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</option>
<?php

							for ($i=0; $i<count($topic); $i++)
							{
								$key = array_keys($topic);
								echo '<option value="'.$key[$i].'"'.((isset($form['topic']) && ($form['topic'] == $key[$i])) ? ' selected="selected"' : '').'>'.forum_htmlencode($topic[$key[$i]]).'</option>';
							}

?>
							</select>
						</span>
					</div>
				</div>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>">
							<span><?php echo $lang_attach['Order by'] ?></span>
						</label><br />
						<span class="fld-input">
							<select id="fld<?php echo $forum_page['fld_count'] ?>" name="form[order]">
								<option <?php echo (!isset($form['order']) || $form['order'] == 'id') ? 'selected="selected"' : '' ?> value="id"><?php echo $lang_attach['Id'] ?></option>
								<option <?php echo (isset($form['order']) && $form['order'] == 'filename') ? 'selected="selected"' : '' ?> value="filename"><?php echo $lang_attach['Filename'] ?></option>
								<option <?php echo (isset($form['order']) && $form['order'] == 'owner_id') ? 'selected="selected"' : '' ?> value="owner_id"><?php echo $lang_attach['Owner'] ?></option>
								<option <?php echo (isset($form['order']) && $form['order'] == 'uploaded_at') ? 'selected="selected"' : '' ?> value="uploaded_at"><?php echo $lang_attach['Up date'] ?></option>
								<option <?php echo (isset($form['order']) && $form['order'] == 'file_mime_type') ? 'selected="selected"' : '' ?> value="file_mime_type"><?php echo $lang_attach['Type'] ?></option>
								<option <?php echo (isset($form['order']) && $form['order'] == 'topic_id') ? 'selected="selected"' : '' ?> value="topic_id"><?php echo $lang_attach['Topic'] ?></option>
								<option <?php echo (isset($form['order']) && $form['order'] == 'post_id') ? 'selected="selected"' : '' ?> value="post_id"><?php echo $lang_attach['Post id'] ?></option>
								<option <?php echo (isset($form['order']) && $form['order'] == 'download_counter') ? 'selected="selected"' : '' ?> value="download_counter"><?php echo $lang_attach['Downloads'] ?></option>
							</select>
						</span>
					</div>
				</div>
				<fieldset class="mf-set set<?php echo ++$forum_page['item_count'] ?>">
					<legend><span><?php echo $lang_attach['Result sort order'] ?></span></legend>
					<div class="mf-box">
						<div class="mf-item">
							<span class="fld-input">
								<input type="radio" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[sort_dir]" value="ASC" <?php echo(($form['sort_dir'] == 'ASC') ? 'checked="checked"' : '') ?> />
							</span>
							<label for="fld<?php echo $forum_page['fld_count'] ?>"><?php echo $lang_attach['Ascending'] ?></label>
						</div>
						<div class="mf-item">
							<span class="fld-input">
								<input type="radio" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[sort_dir]" value="DESC" <?php echo(($form['sort_dir'] == 'DESC') ? 'checked="checked"' : '') ?> />
							</span>
							<label for="fld<?php echo $forum_page['fld_count'] ?>"><?php echo $lang_attach['Descending'] ?></label>
						</div>
					</div>
				</fieldset>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[orphans]" value="1" <?php echo (isset($show_orphans) && ($show_orphans == '1') ? 'checked="checked"' : '') ?> /></span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_attach['Orphans'] ?></span><?php echo $lang_attach['Show only "Orphans"'] ?></label>
					</div>
				</div>
			</fieldset>
			<div class="frm-buttons">
				<input type="submit" name="apply" value="<?php echo $lang_attach['Apply'] ?>" />
			</div>

<?php

if (!empty($attachments))
{

?>

			<div class="ct-group">
				<table cellspacing="0">
					<thead>
						<tr>
							<th class="tc0" scope="col"><?php echo $lang_attach['Filename'] ?></th>
							<th class="tc1" scope="col" style="width: 120px"><?php echo $lang_attach['Filesize'] ?></th>
							<th class="tc2" scope="col" style="width: 90px"><?php echo $lang_attach['Owner'] ?></th>
							<th class="tc3" scope="col"><?php echo $lang_attach['Uploaded date'] ?></th>
							<th class="tc4" scope="col"><?php echo $lang_attach['MIME-type'] ?></th>
							<th class="tc5" scope="col" style="width: 60px"><?php echo $lang_attach['Downloads'] ?></th>
						</tr>
					</thead>

<?php

		foreach ($attachments as $key => $value)
		{
			$args = array();
			$args[] = $key;
			$args[] = generate_form_token('rename'.$forum_user['id']);

?>

					<tbody>
						<tr>
							<td class="tc0" scope="col"><a href="<?php echo forum_link($attach_url['admin_attachment_edit'], array($key)) ?>"><?php echo forum_htmlencode($attachments[$key]['filename']) ?></a></td>
							<td class="tc1" scope="col"><?php echo format_size($attachments[$key]['size']) ?></td>
							<td class="tc2" scope="col"><?php echo forum_htmlencode($attachments[$key]['username']) ?></td>
							<td class="tc3" scope="col"><?php echo format_time($attachments[$key]['uploaded_at']) ?></td>
							<td class="tc4" scope="col"><?php echo $attachments[$key]['file_mime_type'] ?></td>
							<td class="tc5" scope="col"><?php echo $attachments[$key]['download_counter'] ?></td>
						</tr>
					</tbody>
<?php

		}

?>
				</table>
			</div>
<?php

}

?>
		</form>
	</div>
<?php

$tpl_temp = trim(ob_get_contents());
$tpl_main = str_replace('<!-- forum_main -->', $tpl_temp, $tpl_main);
ob_end_clean();
// END SUBST - <!-- forum_main -->

require FORUM_ROOT.'footer.php';

?>