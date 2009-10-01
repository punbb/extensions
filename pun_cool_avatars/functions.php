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

	fwrite($fh, '<?php'."\n\n".'define(\'PHO.TO_TEMPLATES_LOADED\', 1);'."\n\n".'$pho_to_templates = '.var_export($pho_to_templates, true).';'."\n\n".'?>');

	fclose($fh);
}

function rewrite_avatar($pho_to_result_url, $user_id, $type = 'jpg')
{
	global $forum_url, $forum_config, $errors;

	$photo_image = get_remote_file($pho_to_result_url, 10);
	if (!empty($photo_image['content']))
	{
		$avatar_handler = fopen(FORUM_ROOT.$forum_config['o_avatars_dir'].'/'.$user_id.'.'.$type, 'w');
		fwrite($avatar_handler, $photo_image['content']);
		fclose($avatar_handler);
	}
	else
		$errors[] = 'No image';
}

function get_new_avatar($template, $user_id, $type = 'jpg')
{
	global $forum_url, $errors;

	$queue_response = get_remote_file(gen_link($forum_url['pho.to_queue'], array(FREE_KEY, forum_link('img/avatars/'.$user_id.'.'.$type), IMAGE_LIMIT, $template)), 10);
	if (!empty($queue_response['content']))
	{
		if (!defined('FORUM_XML_FUNCTIONS_LOADED'))
			require FORUM_ROOT.'include/xml.php';

		$get_response = xml_to_array(forum_trim($queue_response['content']));
		if (strtolower($get_response['image_process_response']['status']) == 'ok' && !empty($get_response['image_process_response']['request_id']))
		{
			$get_result_response = get_remote_file(gen_link($forum_url['pho.to_get-result'], array($get_response['image_process_response']['request_id'])), 10);
			$get_result_response = xml_to_array($get_result_response['content']);
			if (!empty($get_result_response['image_process_response']['status']))
			{
				switch ($get_result_response['image_process_response']['status'])
				{
					case 'InProgress':
						$errors[] = 'The task is in progress';
						break;
					case 'Error':
						$errors[] = 'Error has occurred while processing the task: '.$get_result_response['image_process_response']['description'];
						break;
					case 'WrongID':
						$errors[] = 'There is no task with such request_id';
						break;
				}
				//Redirect to pho.to page
				if (empty($errors))
					header('Location: '.$get_result_response['image_process_response']['page_to_visit'].'&redirect_url='.urlencode(str_replace('&amp;', '&', forum_link($forum_url['profile_avatar'], array($user_id)))));
			}
			else
				$errors[] = 'Something goes wrong!';
		}
		else
			$errors[] = 'Something goes wrong!';
	}
	else
		$errors[] = 'Service unavailable!';
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

?>