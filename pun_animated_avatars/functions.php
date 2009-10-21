<?php
/**
 * pun_animated_avatars functions
 *
 * @copyright (C) 2008-2009 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_animated_avatars
 */
 
// Make sure no one attempts to run this script "directly"
if (!defined('FORUM'))
	exit;

function pun_animated_avatars_gen_link($url, $args)
{
	for ($i = 0; isset($args[$i]); ++$i)
		$url = str_replace('$'.($i + 1), $args[$i], $url);

	return $url;
}

function pun_animated_avatars_generate_templates_cache()
{
	global $forum_url;

	if (!defined('FORUM_XML_FUNCTIONS_LOADED'))
		require FORUM_ROOT.'include/xml.php';

	$group_reg_exp_pattern = '~group:([a-zA-Z0-9_]+)~';
	$pho_to_templates = array();
	$aet_templates_response = get_remote_file($forum_url['pho.to_AET_templates'], 10);
	if (!empty($aet_templates_response['content']))
	{
		$data_aet_templates = xml_to_array($aet_templates_response['content']);
		if (!empty($data_aet_templates))
		{
			$pho_to_templates['AET']['templates'] = array();
			foreach ($data_aet_templates['response']['template'] as $template)
			{
				//Fetch template group
				preg_match($group_reg_exp_pattern, $template['tags'], $group);
				if (empty($pho_to_templates['AET']['templates'][$group[1]]))
					$pho_to_templates['AET']['templates'][$group[1]] = array();
				$pho_to_templates['AET']['templates'][$group[1]][] = array('name' => $template['name'], 'title' => $template['title'], 'thumb' => $template['thumb']);
			}
			$pho_to_templates['AET']['error'] = FALSE;
		}
		else
			$pho_to_templates['AET']['error'] = TRUE;
	}
	else
		$pho_to_templates['AET']['error'] = TRUE;

	$pho_to_templates['cached'] = time();

	// Output config as PHP code
	$fh = @fopen(FORUM_CACHE_DIR.'cache_pun_animated_avatars.php', 'wb');
	if (!$fh)
		error('Unable to write pho.to templates cache file to cache directory. Please make sure PHP has write access to the directory \'cache\'.', __FILE__, __LINE__);

	fwrite($fh, '<?php'."\n\n".'if (!defined(\'ANIMATED_TEMPLATES_LOADED\')) define(\'ANIMATED_TEMPLATES_LOADED\', 1);'."\n\n".'$animated_templates = '.var_export($pho_to_templates, true).';'."\n\n".'?>');

	fclose($fh);
}

function pun_animated_avatars_template($template, $user_id, $type = 'jpg')
{
	global $forum_url, $errors, $forum_config, $lang_pun_animated_avatars;

	$queue_response = get_remote_file(pun_animated_avatars_gen_link($forum_url['pho.to_AET_queue'], array(PUN_ANIMATED_AVATARS_FREE_KEY, forum_link($forum_config['o_pun_animated_avatars_file_dir'].'/'.$user_id.'.'.$type), PUN_ANIMATED_AVATARS_IMAGE_LIMIT, $template, min($forum_config['o_avatars_width'], $forum_config['o_avatars_height']))), 10);
	if (!empty($queue_response['content']))
	{
		if (!defined('FORUM_XML_FUNCTIONS_LOADED'))
			require FORUM_ROOT.'include/xml.php';

		$get_response = xml_to_array(forum_trim($queue_response['content']));
		if (strtolower($get_response['image_process_response']['status']) == 'ok' && !empty($get_response['image_process_response']['request_id']))
			pun_animated_avatars_visit_pho_to_page($user_id, $get_response['image_process_response']['request_id']);
		else
			$errors[] = $lang_pun_animated_avatars['Pho.to error server'];
	}
	else
		$errors[] = $lang_pun_animated_avatars['Pho.to server unavailable'];
}

function pun_animated_avatars_visit_pho_to_page($user_id, $request_id)
{
	global $forum_url, $errors, $lang_pun_animated_avatars, $forum_user;

	$get_result_response = get_remote_file(pun_animated_avatars_gen_link($forum_url['pho.to_get-result'], array($request_id)), 10);
	if (!empty($get_result_response['content']))
	{
		if (!defined('FORUM_XML_FUNCTIONS_LOADED'))
			require FORUM_ROOT.'include/xml.php';
		$get_result_response = xml_to_array($get_result_response['content']);
	}
	else
		$errors[] = sprintf($lang_pun_animated_avatars['Pho.to error get response'], forum_link($forum_url['animated_avatar'], array($user_id)).'&amp;request_id='.$request_id);

	if (empty($errors) && !empty($get_result_response['image_process_response']['status']))
	{
		switch ($get_result_response['image_process_response']['status'])
		{
			case 'InProgress':
				usleep(170000);
				header('Location: '.str_replace('&amp;', '&', forum_link($forum_url['animated_avatar_request'], array($user_id, $request_id, generate_form_token('request_id'.$forum_user['id'])))));
				$errors[] = sprintf($lang_pun_animated_avatars['Pho.to error task in progress'], forum_link($forum_url['animated_avatar_request'], array($user_id, $request_id, generate_form_token('request_id'.$forum_user['id']))));
				break;
			case 'Error':
				$errors[] = $lang_pun_animated_avatars['Pho.to error other errors'].$get_result_response['image_process_response']['description'];
				break;
			case 'WrongID':
				$errors[] = $lang_pun_animated_avatars['Pho.to error wrongId'];
				break;
		}
		//Redirect to pho.to page
		if (empty($errors))
			header('Location: '.$get_result_response['image_process_response']['page_to_visit'].'&redirect_url='.urlencode(str_replace('&amp;', '&', forum_link($forum_url['animated_avatar'], array($user_id)))));
	}
	else
		$errors[] = $lang_pun_animated_avatars['Pho.to error server'];
}

function pun_animated_avatars_get_avatar_type($user_id)
{
	global $forum_config;

	$avatar_type = FALSE;

	$filetypes = array('jpg', 'gif', 'png');
	foreach ($filetypes as $cur_type)
	{
		if (file_exists(FORUM_ROOT.$forum_config['o_avatars_dir'].'/'.$user_id.'.'.$cur_type))
			return $cur_type;
	}

	return FALSE;
}

function pun_animated_avatars_get_file_type($user_id)
{
	global $forum_config;

	$avatar_type = FALSE;

	$filetypes = array('jpg', 'gif', 'png');
	foreach ($filetypes as $cur_type)
	{
		if (file_exists(FORUM_ROOT.$forum_config['o_pun_animated_avatars_file_dir'].'/'.$user_id.'.'.$cur_type))
			return $cur_type;
	}

	return FALSE;
}
function pun_animated_avatars_upload_photo($id)
{
	global $errors, $forum_config, $lang_profile, $lang_pun_animated_avatars;

	if (!isset($_FILES['req_file']))
		$errors[] = $lang_profile['No file'];
	else
		$uploaded_file = $_FILES['req_file'];
	if (!empty($errors))
		return;
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

	if (is_uploaded_file($uploaded_file['tmp_name']) && empty($errors))
	{
		$allowed_types = array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/png', 'image/x-png');

		if (!in_array($uploaded_file['type'], $allowed_types))
			$errors[] = $lang_profile['Bad type'];
		else
		{
			// Make sure the file isn't too big
			if ($uploaded_file['size'] > $forum_config['o_pun_animated_avatars_max_size'])
				$errors[] = sprintf($lang_profile['Too large'], forum_number_format($forum_config['o_pun_animated_avatars_max_size']));
		}

		if (empty($errors))
		{
			// Determine type
			$extension = null;
			if ($uploaded_file['type'] == 'image/gif')
				$extension = '.gif';
			else if ($uploaded_file['type'] == 'image/jpeg' || $uploaded_file['type'] == 'image/pjpeg')
				$extension = '.jpg';
			else
				$extension = '.png';

			// Move the file to the avatar directory. We do this before checking the width/height to circumvent open_basedir restrictions.
			if (!@move_uploaded_file($uploaded_file['tmp_name'], $forum_config['o_pun_animated_avatars_file_dir'].'/'.$id.'.tmp'))
				$errors[] = sprintf($lang_profile['Move failed'], '<a href="mailto:'.forum_htmlencode($forum_config['o_admin_email']).'">'.forum_htmlencode($forum_config['o_admin_email']).'</a>');

			if (empty($errors))
			{
				// Now check the width/height
				list($width, $height, $type,) = getimagesize($forum_config['o_pun_animated_avatars_file_dir'].'/'.$id.'.tmp');
				if (empty($width) || empty($height) || $width > $forum_config['o_pun_animated_avatars_max_width'] || $height > $forum_config['o_pun_animated_avatars_max_height'])
				{
					@unlink($forum_config['o_pun_animated_avatars_file_dir'].'/'.$id.'.tmp');
					$errors[] = sprintf($lang_profile['Too wide or high'], $forum_config['o_pun_animated_avatars_max_width'], $forum_config['o_pun_animated_avatars_max_height']);
				}
				else if ($type == 1 && $uploaded_file['type'] != 'image/gif')	// Prevent dodgy uploads
				{
					@unlink($forum_config['o_pun_animated_avatars_file_dir'].'/'.$id.'.tmp');
					$errors[] = $lang_profile['Bad type'];
				}

				if (empty($errors))
				{
					pun_animated_avatars_add_file_info($id, array('width' => $width, 'height' => $height));
					// Put the new photo in its place
					@rename($forum_config['o_pun_animated_avatars_file_dir'].'/'.$id.'.tmp', $forum_config['o_pun_animated_avatars_file_dir'].'/'.$id.$extension);
					@chmod($forum_config['o_pun_animated_avatars_file_dir'].'/'.$id.$extension, 0644);
				}
			}
		}
	}
	else if (empty($errors))
		$errors[] = $lang_profile['Unknown failure'];
}

function pun_animated_avatars_remove_photo($user_id)
{
	global $lang_common, $forum_config, $forum_db;

	$type = pun_animated_avatars_get_file_type($user_id);
	if (!$type)
		message($lang_common['Bad request']);
	unlink(FORUM_ROOT.$forum_config['o_pun_animated_avatars_file_dir'].'/'.$user_id.'.'.$type);

	$query = array(
		'DELETE'	=>	'pun_animated_avatars_files',
		'WHERE'		=>	'user_id = '.$user_id
	);
	$forum_db->query_build($query) or error(__FILE__, __LINE__);
}

function pun_animated_avatars_add_file_info($user_id, $file_info)
{
	global $forum_db;

	$query = array(
		'INSERT'	=>	'user_id, width, height',
		'INTO'		=>	'pun_animated_avatars_files',
		'VALUES'	=>	$user_id.', '.$file_info['width'].', '.$file_info['height']
	);
	$forum_db->query_build($query) or error(__FILE__, __LINE__);
}

function pun_animated_avatars_get_file_info($user_id)
{
	global $forum_db;

	$query = array(
		'SELECT'	=>	'width, height',
		'FROM'		=>	'pun_animated_avatars_files',
		'WHERE'		=>	'user_id = '.$user_id
	);
	$res_file_info = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	list($width, $height) = $forum_db->fetch_row($res_file_info);

	return array('width' => $width, 'height' => $height);
}

function pun_animated_avatars_get_result_info($user_id)
{
	global $forum_db;

	$query = array(
		'SELECT'	=>	'result_url',
		'FROM'		=>	'pun_animated_avatars_result_info',
		'WHERE'		=>	'user_id = '.$user_id
	);
	$res_file_info = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	if ($forum_db->num_rows($res_file_info) > 0)
		list($result_url) = $forum_db->fetch_row($res_file_info);
	else
		$result_url = FALSE;

	return $result_url;	
}

function pun_animated_avatars_remove_result_info($user_id)
{
	global $forum_db;

	$query = array(
		'DELETE'	=>	'pun_animated_avatars_result_info',
		'WHERE'		=>	'user_id = '.$user_id
	);
	$forum_db->query_build($query) or error(__FILE__, __LINE__);
}

function pun_animated_avatars_add_result_info($user_id, $result_url)
{
	global $forum_db;

	if (pun_animated_avatars_get_result_info($user_id) === FALSE)
		$query = array(
			'INSERT'	=>	'user_id, result_url',
			'INTO'		=>	'pun_animated_avatars_result_info',
			'VALUES'	=>	$user_id.', \''.$forum_db->escape($result_url).'\''
		);
	else
		$query = array(
			'UPDATE'	=>	'pun_animated_avatars_result_info',
			'SET'		=>	'result_url = \''.$forum_db->escape($result_url).'\'',
			'WHERE'		=>	'user_id = '.$user_id
		);
	
	$forum_db->query_build($query) or error(__FILE__, __LINE__);
}

?>
