<?php

/***********************************************************************

	Copyright (C) 2009 PunBB

	Partially based on Attachment Mod by Frank Hagstrom

	PunBB is free software; you can redistribute it and/or modify it
	under the terms of the GNU General Public License as published
	by the Free Software Foundation; either version 2 of the License,
	or (at your option) any later version.

	PunBB is distributed in the hope that it will be useful, but
	WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston,
	MA  02111-1307  USA

***********************************************************************/

if (!defined('FORUM')) exit;

define('MBYTE', 1048576);
define('KBYTE', 1024);

function attach_allow_upload($upload, $max_size, $file_ext, $upload_size, $upload_name)
{
	global $forum_user, $forum_config, $ext_info, $lang_attach;
			
	$errors = array();

	$attach_allowed = true;
	if ($upload || ($forum_user['g_id'] == FORUM_ADMIN))
		$attach_allowed = true;
	else
		$attach_allowed = false;

	if (($attach_allowed && attach_check_extension($upload_name, $file_ext)) || ($forum_user['g_id'] == FORUM_ADMIN))
		$attach_allowed = true;
	else
		$errors[] = $lang_attach['Bad type'];

	if (($attach_allowed && $upload_size <= $max_size) || ($forum_user['g_id'] == FORUM_ADMIN))
		$attach_allowed = true;
	else
		$errors[] = $lang_attach['Too large ini'];

	return $errors;
}

function attach_icon($file_ext)
{
	global $forum_config, $attach_icons, $forum_user, $lang_attach;

	if ($forum_user['show_img'] == 0 || $forum_config['attach_use_icon'] == 0)
		return '';

	if (empty($attach_icons) && !empty($forum_config['attach_icon_extension']))
	{
		$icon_extension = explode(',', $forum_config['attach_icon_extension']);
		$icon_name = explode(',', $forum_config['attach_icon_name']);
	
		for ($cur_icon = 0; $cur_icon < count($icon_extension); $cur_icon++)
			$attach_icons[$icon_extension[$cur_icon]] = $icon_name[$cur_icon];
	}

	$icon_url = $forum_config['attach_icon_folder'].'unknown.png';

	if (array_key_exists($file_ext, $attach_icons))
		$icon_url = $forum_config['attach_icon_folder'].$attach_icons[$file_ext];

	return '<img src="'.$icon_url.'" width="15" height="15" alt="'.$lang_attach['Attachment icon'].'" />&nbsp;';
}

function attach_generate_pathname($storagepath = '')
{
	global $lang_attach;

	if (empty($storagepath))
		return md5(time().$lang_attach['Put salt'].rand(0, 1E6));

	while (($newdir = attach_generate_pathname()) && is_dir(FORUM_ROOT.$storagepath.$newdir));
	return $newdir;
}



function attach_generate_filename($messagelenght=0, $filesize=0)
{
	global $lang_attach, $forum_config;

	while (($newfile = md5(attach_generate_pathname().$messagelenght.$filesize.$lang_attach['Some more salt keywords']).'.attach') && is_file(FORUM_ROOT.$forum_config['attach_basefolder'].$forum_config['attach_subfolder'].'/'.$newfile));
	return $newfile;
}

function attach_create_attachment($attach_secure_str, $cur_posting)
{
	global $forum_db, $forum_user, $forum_config, $errors, $uploaded_list, $lang_attach;

	if ($cur_posting['g_pun_attachment_allow_upload'] == 1)
	{
		if (count($uploaded_list) + 1 > $cur_posting['g_pun_attachment_files_per_post'])
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
				if (in_array($file_ext, explode(',', $cur_posting['g_pun_attachment_disallowed_extensions'])) || (empty($cur_posting['g_pun_attachment_disallowed_extensions']) && in_array($file_ext, explode(',', $forum_config['attach_always_deny']))))
					$errors[] = sprintf($lang_attach['Ext error'], $file_ext);
				if ($uploaded_file['size'] > $cur_posting['g_pun_attachment_upload_max_size'])
					$errors[] = sprintf($lang_attach['Filesize error'], $cur_posting['g_pun_attachment_upload_max_size']);
			}
		}			
	}
	else
		$errors[] = $lang_attach['Up perm error'];

	if (empty($errors))
	{
		if (is_uploaded_file($uploaded_file['tmp_name']) )
		{		
			$attach_name = attach_generate_filename();
			if (!move_uploaded_file($uploaded_file['tmp_name'], $forum_config['attach_basefolder'].$forum_config['attach_subfolder'].'/'.$attach_name))
				$errors[] = sprintf($lang_profile['Move failed'], '<a href="mailto:'.forum_htmlencode($forum_config['o_admin_email']).'">'.forum_htmlencode($forum_config['o_admin_email']).'</a>');
			if (empty($errors))
			{
				$attach_record = array('owner_id' => 0, 'post_id' => 0, 'topic_id' => 0, 'filename' => '\''.$forum_db->escape($uploaded_file['name']).'\'', 'file_ext' => '\''.$forum_db->escape($file_ext).'\'', 'file_mime_type' => '\''.attach_create_mime($file_ext).'\'', 'file_path' => '\''.$forum_db->escape($forum_config['attach_subfolder'].'/'.$attach_name).'\'', 'size' => $uploaded_file['size'], 'download_counter' => 0, 'uploaded_at' => time(), 'secure_str' => '\''.$forum_db->escape($attach_secure_str).'\'');

				if (in_array($file_ext, array('gif', 'jpg', 'png')))
				{
					list($width, $height,,) = getimagesize(FORUM_ROOT.$forum_config['attach_basefolder'].$forum_config['attach_subfolder'].'/'.$attach_name);
					if (empty($width) || empty($height))
						$errors[] = $lang_attach['Bad image'];
					else
					{
						$attach_record['img_width'] = $width;
						$attach_record['img_height'] = $height;
					}
				}
				else
				{
					$attach_record['img_width'] = 'NULL';
					$attach_record['img_height'] = 'NULL';
				}
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

function attach_check_extension($filename, $allowed_extensions)
{
	global $forum_config;

	$cur_file_extension = attach_get_extension($filename);
	$allowed_extensions = !empty($allowed_extensions) ? explode(',', $allowed_extensions) : array();
	$denied_extensions = !empty($forum_config['attach_always_deny']) ? explode(',', $forum_config['attach_always_deny']) : array();

	foreach ($denied_extensions as $value)
		if ($value == $cur_file_extension)
			return false;

	if (!empty($allowed_extensions))
	{
		foreach ($allowed_extensions as $key => $value)
			if ($value == $cur_file_extension)
				return true;
		return false;
	}

	return true;
}

function attach_delete_attachment($item, $orphans)
{
	global $forum_db, $forum_user, $forum_config, $lang_attach, $forum_page;

	$attach_allowed_delete = '0';

	if ($forum_user['is_admmod'])
	{
		$attach_allowed_delete = '1';
	}
	else
	{
		$query = array(
			'SELECT'	=> 'g.g_pun_attachment_allow_delete, g.g_pun_attachment_allow_delete_own',
			'FROM'		=> 'groups AS g',
			'WHERE'		=> 'g.g_id='.$forum_user['g_id']
		);

		$result = $forum_db->query_build($query) or error (__FILE__,__LINE__);

		$query = array(
			'SELECT'	=> 'af.owner_id',
			'FROM'		=> 'attach_files as af',
			'WHERE'		=> 'af.id='.intval($item).' AND af.owner_id='.$forum_user['id']
		);

		$owner_result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		if ($forum_db->num_rows($result) || $forum_db->num_rows($owner_result))
		{
			$attach = $forum_db->fetch_assoc($result);
			$owner_id = $forum_db->fetch_assoc($owner_result);

			if ($attach['g_pun_attachment_allow_delete'])
			{
				$attach_allowed_delete = '1';
			}
			else if ($forum_db->num_rows($owner_result) && $attach['g_pun_attachment_allow_delete_own'])
			{
				$attach_allowed_delete = '1';
			}
		}
		else
			message('No permission');

	}

	if (($orphans == '0') || ($forum_user['is_admmod'] == '1'))
	{
		$query = array(
			'SELECT'	=> 'file_path',
			'FROM'		=> 'attach_files',
			'WHERE'		=> 'id='.intval($item)
		);

		$result = $forum_db->query_build($query) or error(__FILE__,__LINE__);

		if ($forum_db->num_rows($result))
		{
			$attach = $forum_db->fetch_assoc($result);

			$fp = fopen($forum_config['attach_basefolder'].$attach['file_path'], 'wb');

			if (!$fp)
				message(sprintf($lang_attach['Bad filepointer'], $item));

			fclose($fp);

			$query = array(
				'DELETE'	=> 'attach_files',
				'WHERE'		=> 'id='.intval($item)
			);

			$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

			return true;
		}
	}
	else if (($orphans == '1') && ($forum_user['is_admmod'] == '0'))
	{
		$query = array(
			'UPDATE'	=> 'attach_files',
			'SET'		=> 'post_id=0, topic_id=0, owner_id=0',
			'WHERE'		=> 'id='.intval($item)
		);

		$forum_db->query_build($query) or error(__FILE__, __LINE__);
	}
}

function attach_delete_thread($id, $orphans)
{
	global $forum_db, $forum_config;

	$ok = true;

	if($orphans != '1')
	{
		$query = array(
			'SELECT'	=> 'af.id',
			'FROM'		=> 'attach_files AS af, posts AS p',
			'WHERE'		=> 'af.post_id=p.id AND af.topic_id='.intval($id)
		);

		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		if($forum_db->num_rows($result) > 0)
		{
			while((list($attach_id) = $forum_db->fetch_row($result_attach)) && $ok)
			{
				$ok = attach_delete_attachment($attach_id, $orphans);
			}
		}
	}
}

function attach_delete_post($id, $orphans)
{
	global $forum_db;

	$ok = true;

	$query = array(
		'SELECT'	=> 'af.id',
		'FROM'		=> 'attach_files AS af',
		'WHERE'		=> 'af.post_id='.intval($id)
	);

	$result = $forum_db->query_build($query) or error(__FILE, __LINE__);

	if ($forum_db->num_rows($result) > 0)
	{
		while((list($attach_id) = $forum_db->fetch_row($result)) && $ok)
		{
			$ok = attach_delete_attachment($attach_id, $orphans);
		}
	}
}

function validate_small_image($id)
{
	global $forum_db, $forum_config, $ext_info;

	$query = array(
		'SELECT'	=> 'file_path',
		'FROM'		=> 'attach_files',
		'WHERE'		=> 'id = '.$id
	);

	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	if ($forum_db->num_rows($result))
	{
		$file_path = $forum_db->fetch_assoc($result);

		list ($width, $height, , ) = getimagesize($ext_info['path'].'/attachments/'.$file_path['file_path']);

		if (($height <= $forum_config['attach_small_height']) && ($width <= $forum_config['attach_small_width']))
		{
			$return = array();
			$return['width'] = $width;
			$return['height'] = $height;
		}
		else
		{
			$return = false;
		}
	}
	else
		message($lang_common['Bad request']);

	return $return;
}

function format_size($size)
{
	global $lang_attach;

	if ($size >= MBYTE)
		$size = round($size/MBYTE, 2).$lang_attach['Mb'];
	else if ($size >= KBYTE)
		$size = round($size/KBYTE, 2).$lang_attach['Kb'];
	else
		$size = $size.$lang_attach['B'];

	return $size;
}

function show_attachments($attach_list, $cur_posting)
{
	global $lang_attach, $forum_page, $forum_config, $attach_url;

	if (!empty($attach_list))
	{
		$num = 0;

		foreach ($attach_list as $attach)
		{
			++$num;
			$show_image = in_array($attach['file_ext'], array('png', 'jpg', 'gif', 'tiff')) && ($forum_config['attach_disp_small'] == '1') && ($attach['img_height'] <= $forum_config['attach_small_height']) && ($attach['img_width'] <= $forum_config['attach_small_width']);
			$download_link = !empty($attach['secure_str']) ? forum_link($attach_url['misc_show_secure'], array($attach['id'], $attach['secure_str'])) : forum_link($attach_url['misc_download'], $attach['id']);
			$preview_link = !empty($attach['secure_str']) ? forum_link($attach_url['misc_preview_secure'], array($attach['id'], $attach['secure_str'])) : forum_link($attach_url['misc_preview'], $attach['id']);
			$attach_info = format_size($attach['size']).', '.($attach['download_counter'] ? sprintf($lang_attach['Since'], $attach['download_counter'], date('Y-m-d', $attach['uploaded_at'])) : $lang_attach['Never download']).'&nbsp;';

			?>
			<div class="<?php echo $show_image ? 'ct-set' : 'sf-set'; ?> set<?php echo ++$forum_page['item_count'] ?>">
				<div class="<?php echo $show_image ? 'ct' : 'sf'; ?>-box text">
				<?php if ($show_image):	?>
					<h3 class="hn ct-legend"><?php echo sprintf($lang_attach['Number existing'], $num).'&nbsp;'; ?></h3>
						<p class="avatar-demo"><span><a href="<?php echo $download_link; ?>"><img src="<?php echo $download_link; ?>" title="<?php echo forum_htmlencode($attach['filename']).', '.format_size($attach['size']).', '.$attach['img_width'].' x '.$attach['img_height']; ?>" alt="<?php echo $download_link; ?>" title="<?php echo forum_htmlencode($attach['filename']).', '.format_size($attach['size']).', '.$attach['img_width'].' x '.$attach['img_height']; ?>" /></a></span></p>
						<p><?php echo $attach_info; ?><input type="submit" name="delete_<?php echo $attach['id']; ?>" value="<?php echo $lang_attach['Delete']; ?>"/></p>
				<?php else: ?>
						<label for="fld<?php echo ++$forum_page['fld_count']; ?>"><span><?php echo sprintf($lang_attach['Number existing'], $num).'&nbsp;'; ?></span></label>
						<?php if (in_array($attach['file_ext'], array('png', 'jpg', 'gif', 'tiff'))): ?>
							<a href="<?php echo $preview_link; ?>"><?php echo attach_icon($attach['file_ext']).'&nbsp;'.forum_htmlencode($attach['filename']); ?></a>
						<?php else: ?>
							<a href="<?php echo $download_link; ?>"><?php echo attach_icon($attach['file_ext']).'&nbsp;'.forum_htmlencode($attach['filename']);?></a>
						<?php endif; ?>
						<?php echo $attach_info; ?>
						<input type="submit" name="delete_<?php echo $attach['id']; ?>" value="<?php echo $lang_attach['Delete']; ?>"/>
				<?php endif; ?>
				</div>
			</div>
		<?php
	
		}
	}
	if ($cur_posting['g_pun_attachment_allow_upload'] && count($attach_list) < $cur_posting['g_pun_attachment_files_per_post'])
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
	global $lang_attach, $forum_page, $forum_config, $attach_url;
	
	$result = '<div class="attachments"><strong id="attach'.$post_id.'">'.$lang_attach['Post attachs'].'</strong>';
	if ($cur_topic['g_pun_attachment_allow_download'])
		foreach ($attach_list as $attach)
		{
			$show_image = in_array($attach['file_ext'], array('png', 'jpg', 'gif', 'tiff')) && ($forum_config['attach_disp_small'] == '1') && ($attach['img_height'] <= $forum_config['attach_small_height']) && ($attach['img_width'] <= $forum_config['attach_small_width']);
			$download_link = forum_link($attach_url['misc_download'], $attach['id']);
			$attach_info = format_size($attach['size']).', '.($attach['download_counter'] ? sprintf($lang_attach['Since'], $attach['download_counter'], date('Y-m-d', $attach['uploaded_at'])) : $lang_attach['Never download']).'&nbsp;';
			if ($show_image)
				$link = '<a href="'.$download_link.'"><img src="'.$download_link.'" title="'.forum_htmlencode($attach['filename']).', '.format_size($attach['size']).', '.$attach['img_width'].' x '.$attach['img_height'].'" alt="'.forum_htmlencode($attach['filename']).', '.format_size($attach['size']).', '.$attach['img_width'].' x '.$attach['img_height'].'" /></a>';
			else if (in_array($attach['file_ext'], array('png', 'jpg', 'gif', 'tiff')))
				$link = '<a href="'.forum_link($attach_url['misc_preview'], $attach['id']).'">'.attach_icon($attach['file_ext']).'&nbsp;'.forum_htmlencode($attach['filename']).'</a>';
			else
				$link = '<a href="'.$download_link.'">'.attach_icon($attach['file_ext']).'&nbsp;'.forum_htmlencode($attach['filename']).'</a>';
			$result .= '<p>'.$link;
			if ($show_image)
				$result .= '<br/>'.$attach_info;
			else
				$result .= '&nbsp;'.$attach_info;
			$result .= '</p>';
		}
	else
		$result .= $lang_attach['Download perm error'];
	$result .= '</div>';

	return $result;
}
