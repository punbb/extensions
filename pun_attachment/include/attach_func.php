<?php

/***********************************************************************

	Copyright (C) 2008 PunBB

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

function attach_allow_upload($upload, $max_size, $file_ext, $upload_size, $upload_name)
{
	global $forum_user, $forum_config, $ext_info, $lang_attach;
			
	$errors = array();

	$attach_allowed = '0';

	if(($upload) || ($forum_user['g_id'] == FORUM_ADMIN))
		$attach_allowed = '1';
	else
		$attach_allowed = '0';

	if(($attach_allowed && attach_check_extension($upload_name, $file_ext)) || ($forum_user['g_id'] == FORUM_ADMIN))
		$attach_allowed = '1';
	else
		$errors[] = $lang_attach['Bad type'];

	if((($attach_allowed && $upload_size <= $max_size) || ($forum_user['g_id'] == FORUM_ADMIN)))
		$attach_allowed = '1';
	else
		$errors[] = $lang_attach['Too large ini'];

	return $errors;
}

function attach_icon($file_ext)
{
	global $forum_config, $attach_icons, $forum_user, $lang_attach;

	if(count($attach_icons) == 0 && strlen($forum_config['attach_icon_extension']) != 0)
		attach_fix_icon_array();

	$icon_url = $forum_config['attach_icon_folder'].'unknown.png';

	if($forum_user['show_img'] == 0 || $forum_config['attach_use_icon'] == 0)
		return '';

	if(array_key_exists($file_ext,$attach_icons))
		$icon_url = $forum_config['attach_icon_folder'].$attach_icons[$file_ext];

	return '<img src="'.$icon_url.'" width="15" height="15" alt="'.$lang_attach['Attachment icon'].'" />';	// return the image stuff...
}

function attach_fix_icon_array()
{
	global $forum_config, $attach_icons;

	$icon_extension = explode(',',$forum_config['attach_icon_extension']);
	$icon_name = explode(',',$forum_config['attach_icon_name']);

	for($i = 0; $i < count($icon_extension); $i++)
	{
		$attach_icons[$icon_extension[$i]] = $icon_name[$i];
	}
}

function attach_generate_pathname($storagepath='')
{
	global $lang_attach;
	if(strlen($storagepath) != 0)
	{
		$not_unique='1';

		while($not_unique)
		{
			$newdir = attach_generate_pathname();

			if(!is_dir($storagepath.$newdir))
				return $newdir;
		}
	}
	else
		return substr(md5(time().$lang_attach['Salt keyword']), 0, 32);
}



function attach_generate_filename($storagepath, $messagelenght=0, $filesize=0)
{
	global $lang_attach;
	
	$not_unique='1';

	while($not_unique)
	{
		$newfile = md5(attach_generate_pathname().$messagelenght.$filesize.$lang_attach['Some more salt keywords']).'.attach';

		if(!is_file($storagepath.$newfile))
			return $newfile;
	}
}

function attach_create_attachment($name, $file_mime_type, $size, $tmp_name, $post_id = 0, $messagelenght, $time)
{
	global $forum_db, $forum_user, $forum_config;

	$unique_name = attach_generate_filename($forum_config['attach_basefolder'].$forum_config['attach_subfolder'].'/', $messagelenght, $size, $time);

	if(!move_uploaded_file($tmp_name, $forum_config['attach_basefolder'].$forum_config['attach_subfolder'].'/'.$unique_name))
		error('Unable to move file from: '.$tmp_name.' to '.$forum_config['attach_basefolder'].$forum_config['attach_subfolder'].'/'.$unique_name.'',__FILE__,__LINE__);

	$file_mime_type = attach_create_mime(strpos($name, '.') !== false ? substr($name, -strpos(strrev($name), '.')) : '');

	if ($post_id)
	{
		$query = array(
			'SELECT'	=> 'topic_id,poster_id',
			'FROM'		=> 'posts',
			'WHERE'		=> 'id='.$post_id
		);
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		if ($forum_db->num_rows($result))
		{
			$rules = $forum_db->fetch_assoc($result);

			$topic = $rules['topic_id'];
			$owner_id = $rules['poster_id'];
		}
	}

	$query = array(
		'INSERT'	=> 'post_id, filename, file_ext, file_mime_type, file_path, size, uploaded_at',
		'INTO'		=> 'attach_files',
		'VALUES'	=> $post_id.', \''.$forum_db->escape($name).'\',\''.attach_get_extension($name).'\',\''.$forum_db->escape($file_mime_type).'\',\''.$forum_db->escape($forum_config['attach_subfolder'].'/'.$unique_name).'\', '.$size.', '.$time
	);

	if (isset($topic))
	{
		$query['INSERT'] .= ', topic_id';
		$query['VALUES'] .= ', '.$topic;
	}
	if (isset($owner_id))
	{
		$query['INSERT'] .= ', owner_id';
		$query['VALUES'] .= ', '.$owner_id;
	}

	$result = $forum_db->query_build($query) or error(__FILE__,__LINE__);

	$query = array(
		'SELECT'	=> 'id',
		'FROM'		=> 'attach_files',
		'WHERE'		=> 'file_path=\''.$forum_db->escape($forum_config['attach_subfolder'].'/'.$unique_name).'\''
	);

	$id = $forum_db->query_build($query) or error(__FILE__,__LINE__);

	$id = $forum_db->result($id, 0);

	return $id;
}

function attach_create_subfolder($newfolder)
{
	global $forum_db, $forum_config, $forum_user;

	if(strlen($newfolder) == 0 || $forum_user['g_id'] != FORUM_ADMIN)
		return false;

	if(!is_dir($forum_config['attach_basefolder'].$newfolder))
	{
		if(!mkdir($forum_config['attach_basefolder'].$newfolder, 0750))
			error('Unable to create new subfolder with name \''.$forum_config['attach_basefolder'].$newfolder.'\' with mode 0750',__FILE__,__LINE__);
		if(!copy($forum_config['attach_basefolder'].'.htaccess', $forum_config['attach_basefolder'].$newfolder.'/.htaccess'))
			error('Unable to copy .htaccess file to new subfolder with name \''.$forum_config['attach_basefolder'].$newfolder.'\'',__FILE__,__LINE__);
		if(!copy($forum_config['attach_basefolder'].'index.html', $forum_config['attach_basefolder'].$newfolder.'/index.html'))
			error('Unable to copy index.html file to new subfolder with name \''.$forum_config['attach_basefolder'].$newfolder.'\'',__FILE__,__LINE__);
	}

	$form = array('subfolder' => $newfolder);

	while (list($key, $input) = @each($form))
	{
		if ($forum_config['attach_'.$key] != $input)
		{
			if ($input != '' || is_int($input))
				$value = '\''.$forum_db->escape($input).'\'';
			else
				$value = 'NULL';

			$query = array(
				'UPDATE'	=> 'config',
				'SET'		=> 'conf_value='.$value,
				'WHERE'		=> 'conf_name=\'attach_'.$key.'\''
			);

			$forum_db->query_build($query) or error(__FILE__, __LINE__);
		}
	}
	require_once FORUM_ROOT.'include/cache.php';

	generate_config_cache();

	return true;
}

function attach_create_mime($file_ext='')
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
		'msdownload'		=>		'application/x-msdownload'
	);

	return isset($mimecodes[$file_ext]) ? $mimecodes[$file_ext] : 'application/octet-stream';
}


function attach_get_extension($filename)
{
	if(strlen($filename) == 0)
		return '';

	return strtolower(ltrim(strrchr($filename, "."), "."));
}

function attach_check_extension($filename, $allowed_extensions)
{
	global $forum_config;

	$cur_file_extension = attach_get_extension($filename);

	$allowed_extensions = (strlen($allowed_extensions) != 0) ? explode(',', $allowed_extensions) : array();

	$denied_extensions = (strlen($forum_config['attach_always_deny']) != 0) ? explode(',', $forum_config['attach_always_deny']) : array();

	foreach($denied_extensions as $value)
	{
		if($value == $cur_file_extension)
			return false;
	}

	if(count($allowed_extensions) != 0)
	{
		foreach($allowed_extensions AS $key => $value)
		{
			if($value == $cur_file_extension)
				return true;
		}
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
			$attach_info = $forum_db->fetch_assoc($result);
			$owner_id = $forum_db->fetch_assoc($owner_result);

			if ($attach_info['g_pun_attachment_allow_delete'])
			{
				$attach_allowed_delete = '1';
			}
			else if ($forum_db->num_rows($owner_result) && $attach_info['g_pun_attachment_allow_delete_own'])
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
			$attach_info = $forum_db->fetch_assoc($result);

			$fp = fopen($forum_config['attach_basefolder'].$attach_info['file_path'], 'wb');

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
	global $forum_db, $forum_config;
	global $ext_info;

	$query = array(
		'SELECT'	=> 'file_path',
		'FROM'		=> 'attach_files',
		'WHERE'		=> 'id='.$id
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
