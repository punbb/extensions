<?php
/**
 * pun_cool_avatars functions
 *
 * @copyright (C) 2008-2009 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_cool_avatars
 */
 
// Make sure no one attempts to run this script "directly"
if (!defined('FORUM'))
	exit;

function gen_link($url, $args)
{
	for ($i = 0; isset($args[$i]); ++$i)
		$url = str_replace('$'.($i + 1), $args[$i], $url);

	return $url;
}

function generate_templates_cache()
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
				$pho_to_templates['AET']['templates'][$group[1]][] = $template['name'];
			}
			$pho_to_templates['AET']['error'] = FALSE;
		}
		else
			$pho_to_templates['AET']['error'] = TRUE;
	}
	else
		$pho_to_templates['AET']['error'] = TRUE;
	$fet_templates_response = get_remote_file($forum_url['pho.to_FET_templates'], 10);
	if (!empty($fet_templates_response['content']))
	{
		$data_fet_templates = xml_to_array($fet_templates_response['content']);
		if (!empty($data_fet_templates))
		{
			$pho_to_templates['FET']['templates'] = array();
			foreach ($data_fet_templates['response']['template'] as $template)
			{
				//Fetch template group
				preg_match($group_reg_exp_pattern, $template['tags'], $group);
				if (empty($pho_to_templates['FET']['templates'][$group[1]]))
					$pho_to_templates['FET']['templates'][$group[1]] = array();
				$pho_to_templates['FET']['templates'][$group[1]][] = $template['name'];
			}
			$pho_to_templates['FET']['error'] = FALSE;
		}
		else
			$pho_to_templates['FET']['error'] = TRUE;
	}
	else
		$pho_to_templates['FET']['error'] = TRUE;

	$pho_to_templates['cached'] = time();

	// Output config as PHP code
	$fh = @fopen(FORUM_CACHE_DIR.'cache_pho_to_templates.php', 'wb');
	if (!$fh)
		error('Unable to write pho.to templates cache file to cache directory. Please make sure PHP has write access to the directory \'cache\'.', __FILE__, __LINE__);

	fwrite($fh, '<?php'."\n\n".'if (!defined(\'PHO.TO_TEMPLATES_LOADED\')) define(\'PHO.TO_TEMPLATES_LOADED\', 1);'."\n\n".'$pho_to_templates = '.var_export($pho_to_templates, true).';'."\n\n".'?>');

	fclose($fh);
}

function rewrite_avatar($pho_to_result_url, $user_id, $type = 'jpg')
{
	global $forum_url, $forum_config, $errors, $lang_pun_cool_avatars;

	$photo_image = get_remote_file($pho_to_result_url, 10);
	if (!empty($photo_image['content']))
	{
		if (file_exists(FORUM_ROOT.$forum_config['o_avatars_dir'].'/'.$user_id.'.'.$type))
			unlink(FORUM_ROOT.$forum_config['o_avatars_dir'].'/'.$user_id.'.'.$type);
		$avatar_handler = fopen(FORUM_ROOT.$forum_config['o_avatars_dir'].'/'.$user_id.'.'.substr($pho_to_result_url, strrpos($pho_to_result_url, '.') + 1), 'w');
		fwrite($avatar_handler, $photo_image['content']);
		fclose($avatar_handler);
	}
	else
		$errors[] = $lang_pun_cool_avatars['Pho.to error result image'];
}

function apply_aet_template($template, $user_id, $type = 'jpg')
{
	global $forum_url, $errors, $forum_config, $lang_pun_cool_avatars;

	$queue_response = get_remote_file(gen_link($forum_url['pho.to_AET_queue'], array(FREE_KEY, forum_link($forum_config['o_pun_cool_avatars_file_dir'].'/'.$user_id.'.'.$type), IMAGE_LIMIT, $template, min($forum_config['o_avatars_width'], $forum_config['o_avatars_height']))), 10);
	if (!empty($queue_response['content']))
	{
		if (!defined('FORUM_XML_FUNCTIONS_LOADED'))
			require FORUM_ROOT.'include/xml.php';

		$get_response = xml_to_array(forum_trim($queue_response['content']));
		if (strtolower($get_response['image_process_response']['status']) == 'ok' && !empty($get_response['image_process_response']['request_id']))
			visit_pho_to_page($user_id, $get_response['image_process_response']['request_id']);
		else
			$errors[] = $lang_pun_cool_avatars['Pho.to error server'];
	}
	else
		$errors[] = $lang_pun_cool_avatars['Pho.to server unavailable'];
}

function apply_fet_template($template, $user_id, $type = 'jpg', $auto_crop = 'FALSE')
{
	global $forum_url, $errors, $forum_config, $lang_pun_cool_avatars;

	$queue_response = get_remote_file(gen_link($forum_url['pho.to_FET_queue'], array(FREE_KEY, forum_link($forum_config['o_pun_cool_avatars_file_dir'].'/'.$user_id.'.'.$type), IMAGE_LIMIT, $template, $auto_crop, min($forum_config['o_avatars_width'], $forum_config['o_avatars_height']))), 10);
	if (!empty($queue_response['content']))
	{
		if (!defined('FORUM_XML_FUNCTIONS_LOADED'))
			require FORUM_ROOT.'include/xml.php';

		$get_response = xml_to_array(forum_trim($queue_response['content']));
		if (strtolower($get_response['image_process_response']['status']) == 'ok' && !empty($get_response['image_process_response']['request_id']))
			visit_pho_to_page($user_id, $get_response['image_process_response']['request_id']);
		else
			$errors[] = $lang_pun_cool_avatars['Pho.to error server'];
	}
	else
		$errors[] = $lang_pun_cool_avatars['Pho.to server unavailable'];
}

function visit_pho_to_page($user_id, $request_id)
{
	global $forum_url, $errors, $lang_pun_cool_avatars;

	$get_result_response = get_remote_file(gen_link($forum_url['pho.to_get-result'], array($request_id)), 10);
	if (!empty($get_result_response['content']))
	{
		if (!defined('FORUM_XML_FUNCTIONS_LOADED'))
			require FORUM_ROOT.'include/xml.php';
		$get_result_response = xml_to_array($get_result_response['content']);
	}
	else
		$errors[] = sprintf($lang_pun_cool_avatars['Pho.to error get response'], forum_link($forum_url['edit_avatar'], array($user_id)).'&amp;request_id='.$request_id);

	if (empty($errors) && !empty($get_result_response['image_process_response']['status']))
	{
		switch ($get_result_response['image_process_response']['status'])
		{
			case 'InProgress':
				$errors[] = sprintf($lang_pun_cool_avatars['Pho.to error task in progress'], forum_link($forum_url['edit_avatar'], array($user_id)).'&amp;request_id='.$request_id);
				break;
			case 'Error':
				$errors[] = $lang_pun_cool_avatars['Pho.to error other errors'].$get_result_response['image_process_response']['description'];
				break;
			case 'WrongID':
				$errors[] = $lang_pun_cool_avatars['Pho.to error wrongId'];
				break;
		}
		//Redirect to pho.to page
		if (empty($errors))
			header('Location: '.$get_result_response['image_process_response']['page_to_visit'].'&redirect_url='.urlencode(str_replace('&amp;', '&', forum_link($forum_url['edit_avatar'], array($user_id)))));
	}
	else
		$errors[] = $lang_pun_cool_avatars['Pho.to error server'];
}

function get_avatar_type($user_id)
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

function get_file_type($user_id)
{
	global $forum_config;

	$avatar_type = FALSE;

	$filetypes = array('jpg', 'gif', 'png');
	foreach ($filetypes as $cur_type)
	{
		if (file_exists(FORUM_ROOT.$forum_config['o_pun_cool_avatars_file_dir'].'/'.$user_id.'.'.$cur_type))
			return $cur_type;
	}

	return FALSE;
}
function upload_photo($id)
{
	global $errors, $forum_config, $lang_profile, $lang_pun_cool_avatars;

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
			if ($uploaded_file['size'] > $forum_config['o_pun_cool_avatars_max_size'])
				$errors[] = sprintf($lang_profile['Too large'], forum_number_format($forum_config['o_pun_cool_avatars_max_size']));
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
			if (!@move_uploaded_file($uploaded_file['tmp_name'], $forum_config['o_pun_cool_avatars_file_dir'].'/'.$id.'.tmp'))
				$errors[] = sprintf($lang_profile['Move failed'], '<a href="mailto:'.forum_htmlencode($forum_config['o_admin_email']).'">'.forum_htmlencode($forum_config['o_admin_email']).'</a>');

			if (empty($errors))
			{
				// Now check the width/height
				list($width, $height, $type,) = getimagesize($forum_config['o_pun_cool_avatars_file_dir'].'/'.$id.'.tmp');
				if (empty($width) || empty($height) || $width > $forum_config['o_pun_cool_avatars_max_width'] || $height > $forum_config['o_pun_cool_avatars_max_height'])
				{
					@unlink($forum_config['o_pun_cool_avatars_file_dir'].'/'.$id.'.tmp');
					$errors[] = sprintf($lang_profile['Too wide or high'], $forum_config['o_pun_cool_avatars_max_width'], $forum_config['o_pun_cool_avatars_max_height']);
				}
				else if ($type == 1 && $uploaded_file['type'] != 'image/gif')	// Prevent dodgy uploads
				{
					@unlink($forum_config['o_pun_cool_avatars_file_dir'].'/'.$id.'.tmp');
					$errors[] = $lang_profile['Bad type'];
				}

				if (empty($errors))
				{
					// Put the new avatar in its place
					@rename($forum_config['o_pun_cool_avatars_file_dir'].'/'.$id.'.tmp', $forum_config['o_pun_cool_avatars_file_dir'].'/'.$id.$extension);
					@chmod($forum_config['o_pun_cool_avatars_file_dir'].'/'.$id.$extension, 0644);
				}
			}
		}
	}
	else if (empty($errors))
		$errors[] = $lang_profile['Unknown failure'];
}

function remove_photo($user_id)
{
	global $lang_common, $forum_config;

	$type = get_file_type($user_id);
	if (!$type)
		message($lang_common['Bad request']);
	unlink(FORUM_ROOT.$forum_config['o_pun_cool_avatars_file_dir'].'/'.$user_id.'.'.$type);
}

?>
