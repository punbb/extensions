<?php

/**
 * Functions for pun_attachment extension.
 *
 * @copyright (C) 2008-2012 PunBB, partially based on Attachment Mod by Frank Hagstrom
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_attachment
 */

if (!defined('FORUM')) exit;

define('MBYTE', 1048576);
define('KBYTE', 1024);


function attach_icon($file_ext) {
	global $forum_config, $forum_user, $lang_attach;

	if ($forum_user['show_img'] == 0 || $forum_config['attach_use_icon'] == 0)
		return '';

	$icon_url = $forum_config['attach_icon_folder'].'unknown.png';
	if (!empty($forum_config['attach_icon_extension']))
	{
		$icon_extension = explode(',', $forum_config['attach_icon_extension']);
		$icon_name = explode(',', $forum_config['attach_icon_name']);
		$icon_index = array_search($file_ext, $icon_extension);
		if ($icon_index !== FALSE)
			$icon_url = $forum_config['attach_icon_folder'].$icon_name[$icon_index];
	}

	return '<img src="'.$icon_url.'" width="15" height="15" alt="'.$lang_attach['Attachment icon'].'" />&nbsp;';
}


function attach_generate_pathname($storagepath = '') {
	global $lang_attach;

	if (empty($storagepath))
		return md5(time().$lang_attach['Put salt'].rand(0, 1E6));

	while (($newdir = attach_generate_pathname()) && is_dir(FORUM_ROOT.$storagepath.$newdir));
	return $newdir;
}


function attach_generate_filename($messagelenght=0, $filesize=0) {
	global $lang_attach, $forum_config;

	while (($newfile = md5(attach_generate_pathname().$messagelenght.$filesize.$lang_attach['Some more salt keywords']).'.attach') && is_file(FORUM_ROOT.$forum_config['attach_basefolder'].$forum_config['attach_subfolder'].'/'.$newfile));

	return $newfile;
}


function attach_create_attachment($attach_secure_str, $cur_posting) {
	global $forum_db, $forum_user, $forum_config, $errors, $uploaded_list, $lang_attach;

	if ($forum_user['g_id'] == FORUM_ADMIN || $cur_posting['g_pun_attachment_allow_upload'] == 1)
	{
		if ($forum_user['g_id'] != FORUM_ADMIN && count($uploaded_list) + 1 > $cur_posting['g_pun_attachment_files_per_post'])
			$errors[] = sprintf($lang_attach['Attach limit error'], $cur_posting['g_pun_attachment_files_per_post']);
		else
		{
			// Load the profile.php language file
			require FORUM_ROOT.'lang/'.$forum_user['language'].'/profile.php';
			if (!isset($_FILES['attach_file']))
				$errors[] = $lang_profile['No file'];
			else
				$uploaded_file = $_FILES['attach_file'];

			// Make sure the upload went smooth
			if (isset($uploaded_file['error']) && empty($errors))
			{
				switch ($uploaded_file['error'])
				{
					case 1:	// UPLOAD_ERR_INI_SIZE
					case 2:	// UPLOAD_ERR_FORM_SIZE
						$errors[] = $lang_profile['Too large ini'];
						break;

					case 3:	// UPLOAD_ERR_PARTIAL
						$errors[] = $lang_profile['Partial upload'];
						break;

					case 4:	// UPLOAD_ERR_NO_FILE
						$errors[] = $lang_profile['No file'];
						break;

					case 6:	// UPLOAD_ERR_NO_TMP_DIR
						$errors[] = $lang_profile['No tmp directory'];
						break;

					default:
						// No error occured, but was something actually uploaded?
						if ($uploaded_file['size'] == 0)
							$errors[] = $lang_profile['No file'];
						break;
				}
			}

			if (empty($errors))
			{
				$file_ext = attach_get_extension($uploaded_file['name']);
				if (!in_array($file_ext, explode(',', $cur_posting['g_pun_attachment_disallowed_extensions'])) && in_array($file_ext, explode(',', $forum_config['attach_always_deny'])))
					$errors[] = sprintf($lang_attach['Ext error'], $file_ext);

				if ($forum_user['g_id'] != FORUM_ADMIN && $uploaded_file['size'] > $cur_posting['g_pun_attachment_upload_max_size'])
					$errors[] = sprintf($lang_attach['Filesize error'], $cur_posting['g_pun_attachment_upload_max_size']);

				if (utf8_strlen($uploaded_file['name']) > 255)
					$errors[] = $lang_attach['File len err'];

				if (utf8_strlen($file_ext) > 64)
					$errors[] = $lang_attach['Ext len err'];
			}
		}
	}
	else
		$errors[] = $lang_attach['Up perm error'];

	if (empty($errors))
	{
		if (is_uploaded_file($uploaded_file['tmp_name']))
		{
			$attach_name = attach_generate_filename();
			if (!move_uploaded_file($uploaded_file['tmp_name'], $forum_config['attach_basefolder'].$forum_config['attach_subfolder'].'/'.$attach_name)) {
				$errors[] = sprintf($lang_profile['Move failed'], '<a href="mailto:'.forum_htmlencode($forum_config['o_admin_email']).'">'.forum_htmlencode($forum_config['o_admin_email']).'</a>');
			}

			if (empty($errors))
			{
				$attach_record = array('owner_id' => 0, 'post_id' => 0, 'topic_id' => 0, 'filename' => '\''.$forum_db->escape($uploaded_file['name']).'\'', 'file_ext' => '\''.$forum_db->escape($file_ext).'\'', 'file_mime_type' => '\''.attach_create_mime($file_ext).'\'', 'file_path' => '\''.$forum_db->escape($forum_config['attach_subfolder'].'/'.$attach_name).'\'', 'size' => $uploaded_file['size'], 'download_counter' => 0, 'uploaded_at' => time(), 'secure_str' => '\''.$forum_db->escape($attach_secure_str).'\'');
				if (empty($errors))
				{
					$attach_query = array(
						'INSERT'	=>	implode(',', array_keys($attach_record)),
						'INTO'		=>	'attach_files',
						'VALUES'	=>	implode(',', array_values($attach_record))
					);
					$forum_db->query_build($attach_query) or error(__FILE__, __LINE__);

					$attach_record['id'] = $forum_db->insert_id();
					$attach_record['filename'] = $forum_db->escape($uploaded_file['name']);
					$attach_record['file_ext'] = $forum_db->escape($file_ext);
					$attach_record['secure_str'] = $attach_secure_str;
					$attach_record['file_path'] = $forum_db->escape($forum_config['attach_subfolder'].DIRECTORY_SEPARATOR.$attach_name);
					$uploaded_list[] = $attach_record;
				}
			}
		}
	}
}


function attach_create_mime($file_ext = '')
{
	$mimecodes = array(
		'rtf' 			=>		'text/richtext',
		'html'			=>		'text/html',
		'htm'			=>		'text/html',
		'aiff'			=>		'audio/x-aiff',
		'iff'			=>		'audio/x-aiff',
		'basic'			=>		'audio/basic',
		'wav'			=>		'audio/wav',
		'gif'			=>		'image/gif',
		'jpg'			=>		'image/jpeg',
		'jpeg'			=>		'image/pjpeg',
		'tif'			=>		'image/tiff',
		'png'			=>		'image/x-png',
		'xbm'			=>		'image/x-xbitmap',
		'bmp'			=>		'image/bmp',
		'xjg'			=>		'image/x-jg',
		'emf'			=>		'image/x-emf',
		'wmf'			=>		'image/x-wmf',
		'avi'			=>		'video/avi',
		'mpg'			=>		'video/mpeg',
		'mpeg'			=>		'video/mpeg',
		'ps'			=>		'application/postscript',
		'b64'			=>		'application/base64',
		'macbinhex'		=>		'application/macbinhex40',
		'pdf'			=>		'application/pdf',
		'xzip'			=>		'application/x-compressed',
		'zip'			=>		'application/x-zip-compressed',
		'gzip'			=>		'application/x-gzip-compressed',
		'java'			=>		'application/java',
		'msdownload'	=>		'application/x-msdownload'
	);

	return isset($mimecodes[$file_ext]) ? $mimecodes[$file_ext] : 'application/octet-stream';
}


function attach_get_extension($filename)
{
	if (empty($filename))
		return '';

	return strtolower(ltrim(strrchr($filename, '.'), '.'));
}


function format_size($size) {
	global $lang_attach;

	if ($size >= MBYTE)
		$size = round($size/MBYTE, 2).$lang_attach['Mb'];
	else if ($size >= KBYTE)
		$size = round($size/KBYTE, 2).$lang_attach['Kb'];
	else
		$size = $size.$lang_attach['B'];

	return $size;
}


function show_attachments($attach_list, $cur_posting) {
	global $lang_attach, $forum_page, $forum_config, $attach_url, $forum_user;

	if (!empty($attach_list))
	{
		$num = 0;
		foreach ($attach_list as $attach)
		{
			++$num;
			if (in_array($attach['file_ext'], array('png', 'jpg', 'gif', 'tiff')) && $forum_config['attach_disp_small'] == '1')
			{
				list($width, $height,,) = getimagesize(FORUM_ROOT.$forum_config['attach_basefolder'].$attach['file_path']);
				$attach['img_width'] = $width;
				$attach['img_height'] = $height;
				$show_image = ($attach['img_height'] <= $forum_config['attach_small_height']) && ($attach['img_width'] <= $forum_config['attach_small_width']);
			}
			else
				$show_image = false;

			$download_link = !empty($attach['secure_str']) ? forum_link($attach_url['misc_download_secure'], array($attach['id'], $attach['secure_str'])) : forum_link($attach_url['misc_download'], $attach['id']);
			$view_link = !empty($attach['secure_str']) ? forum_link($attach_url['misc_view_secure'], array($attach['id'], $attach['secure_str'])) : forum_link($attach_url['misc_view'], $attach['id']);
			$attach_info = format_size($attach['size']).', '.($attach['download_counter'] ? sprintf($lang_attach['Since'], $attach['download_counter'], date('Y-m-d', $attach['uploaded_at'])) : $lang_attach['Never download']).'&nbsp;';

			?>
			<div class="<?php echo $show_image ? 'ct-set' : 'sf-set'; ?> set<?php echo ++$forum_page['item_count'] ?>">
				<div class="<?php echo $show_image ? 'ct' : 'sf'; ?>-box text">
				<?php if ($show_image):	?>
					<h3 class="hn ct-legend"><?php echo sprintf($lang_attach['Number existing'], $num).'&nbsp;'; ?></h3>
						<p class="show-image"><span><a href="<?php echo $download_link; ?>"><img width="<?php echo $attach['img_width'] > 660 ? '100%' : $attach['img_width'].'px'; ?>" src="<?php echo $view_link; ?>" title="<?php echo forum_htmlencode($attach['filename']).', '.format_size($attach['size']).', '.$attach['img_width'].' x '.$attach['img_height']; ?>" alt="<?php echo $view_link; ?>"/></a></span></p>
						<?php echo forum_htmlencode($attach['filename']).'&nbsp;'.$attach_info;  if ($forum_user['g_pun_attachment_allow_delete'] || !empty($attach['secure_str']) || ($forum_user['g_pun_attachment_allow_delete_own'] && $forum_user['id'] == $attach['owner_id'])): ?>
						<input type="submit" name="delete_<?php echo $attach['id']; ?>" value="<?php echo $lang_attach['Delete button']; ?>"/>
						<?php endif; ?>
				<?php else: ?>
						<label for="fld<?php echo ++$forum_page['fld_count']; ?>"><span><?php echo sprintf($lang_attach['Number existing'], $num).'&nbsp;'; ?></span></label>
						<?php if (in_array($attach['file_ext'], array('png', 'jpg', 'gif', 'tiff'))): ?>
							<a href="<?php echo !empty($attach['secure_str']) ? forum_link($attach_url['misc_preview_secure'], array($attach['id'], $attach['secure_str'])) : forum_link($attach_url['misc_preview'], $attach['id']); ?>"><?php echo attach_icon($attach['file_ext']).forum_htmlencode($attach['filename']); ?></a>
						<?php else: ?>
							<a href="<?php echo $download_link; ?>"><?php echo attach_icon($attach['file_ext']).forum_htmlencode($attach['filename']);?></a>
						<?php endif;
						echo $attach_info;
						if ($forum_user['g_pun_attachment_allow_delete'] || !empty($attach['secure_str']) || ($forum_user['g_pun_attachment_allow_delete_own'] && $forum_user['id'] == $attach['owner_id'])): ?>
						<input type="submit" name="delete_<?php echo $attach['id']; ?>" value="<?php echo $lang_attach['Delete button']; ?>"/>
						<?php endif; ?>
				<?php endif; ?>
				</div>
			</div>
		<?php

		}
	}

	if ($forum_user['g_id'] == FORUM_ADMIN || ($cur_posting['g_pun_attachment_allow_upload'] && count($attach_list) < $cur_posting['g_pun_attachment_files_per_post']))
	{
	?>
		<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
			<div class="sf-box text">
				<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_attach['Attachment'] ?></span></label><br />
				<span class="fld-input">
					<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $cur_posting['g_pun_attachment_upload_max_size']; ?>" />
					<input type="file" id="fld<?php echo $forum_page['fld_count'] ?>" name="attach_file" />
					<input type="submit" name="add_file" value="<?php echo $lang_attach['Add file']; ?>"/>
				</span>
			</div>
		</div>
	<?php
	}
}


function show_attachments_post($attach_list, $post_id, $cur_topic)
{
	global $lang_attach, $forum_page, $forum_config, $attach_url, $forum_user;

	$result = '<div class="attachments"><strong id="attach'.$post_id.'">'.$lang_attach['Post attachs'].'</strong>';
	$allow_downloading = $forum_user['g_id'] == FORUM_ADMIN || $cur_topic['g_pun_attachment_allow_download'];
	foreach ($attach_list as $attach)
	{
		if (in_array($attach['file_ext'], array('png', 'jpg', 'gif', 'tiff')) && $forum_config['attach_disp_small'] == '1')
		{
			list($width, $height,,) = getimagesize(FORUM_ROOT.$forum_config['attach_basefolder'].$attach['file_path']);
			$attach['img_width'] = $width;
			$attach['img_height'] = $height;
			$show_image = ($attach['img_height'] <= $forum_config['attach_small_height']) && ($attach['img_width'] <= $forum_config['attach_small_width']);
		}
		else
			$show_image = false;
		$download_link = forum_link($attach_url['misc_download'], $attach['id']);

		$attach_info = format_size($attach['size']).', '.($attach['download_counter'] ? sprintf($lang_attach['Since'], $attach['download_counter'], date('Y-m-d', $attach['uploaded_at'])) : $lang_attach['Never download']).'&nbsp;';
		if ($allow_downloading)
		{
			if ($show_image)
				$link = '<a href="'.$download_link.'"><img src="'.forum_link($attach_url['misc_view'], $attach['id']).'" title="'.forum_htmlencode($attach['filename']).', '.format_size($attach['size']).', '.$attach['img_width'].' x '.$attach['img_height'].'" alt="'.forum_htmlencode($attach['filename']).', '.format_size($attach['size']).', '.$attach['img_width'].' x '.$attach['img_height'].'" /></a>';
			else if (in_array($attach['file_ext'], array('png', 'jpg', 'gif', 'tiff')))
				$link = '<a href="'.forum_link($attach_url['misc_preview'], $attach['id']).'">'.attach_icon($attach['file_ext']).forum_htmlencode($attach['filename']).'</a>';
			else
				$link = '<a href="'.$download_link.'">'.attach_icon($attach['file_ext']).forum_htmlencode($attach['filename']).'</a>';
		}
		else
			$link = '<b>'.forum_htmlencode($attach['filename']).'</b>';
		$result .= '<p>'.$link;
		if ($show_image)
			$result .= '<br/>'.forum_htmlencode($attach['filename']).'&nbsp;'.$attach_info;
		else
			$result .= '&nbsp;'.$attach_info;
		$result .= '</p>';
	}

	if (!$allow_downloading) {
		$result .= $lang_attach['Download perm error'];
	}

	$result .= '</div>';

	return $result;
}


function get_bytes($value) {
	$value = trim($value);
	$last = strtolower($value[strlen($value) - 1]);
	switch ($last)
	{
		case 'g':
			$value *= MBYTE * KBYTE;
		case 'm':
			$value *= MBYTE;
		case 'k':
			$value *= KBYTE;
	}

	return $value;
}


function remove_attachments($query, $cur_posting) {
	global $forum_page, $forum_config, $forum_db, $forum_user;

	$orphans_id = array();
	$remove_id = array();

	$attach_result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	while ($cur_attach = $forum_db->fetch_assoc($attach_result))
	{
		if ($forum_user['g_id'] == FORUM_ADMIN || $cur_posting['g_pun_attachment_allow_delete'] || $cur_posting['g_pun_attachment_allow_delete_own'])
		{
			if ($forum_user['g_id'] != FORUM_ADMIN && $cur_posting['g_pun_attachment_allow_delete_own'] && $cur_attach['owner_id'] != $forum_user['id'])
			{
				$orphans_id[] = $cur_attach['id'];
				break;
			}
			if (!$forum_config['attach_create_orphans'])
			{
				unlink(FORUM_ROOT.$forum_config['attach_basefolder'].$cur_attach['file_path']);
				$remove_id[] = $cur_attach['id'];
			}
			else
				$orphans_id[] = $cur_attach['id'];
		}
		else
			$orphans_id[] = $cur_attach['id'];
	}

	if (!empty($orphans_id))
	{
		$attach_query = array(
			'UPDATE'	=>	'attach_files',
			'SET'		=>	'post_id = 0, topic_id = 0, owner_id = 0',
			'WHERE'		=>	'id IN ('.implode(',', $orphans_id).')'
		);
		$forum_db->query_build($attach_query) or error(__FILE__, __LINE__);
	}

	if (!empty($remove_id))
	{
		$attach_query = array(
			'DELETE'	=>	'attach_files',
			'WHERE'		=>	'id IN ('.implode(',', $remove_id).')'
		);
		$forum_db->query_build($attach_query) or error(__FILE__, __LINE__);
	}
}