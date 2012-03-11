<?php

/**
 * pun_colored_usergroups functions file
 *
 * @copyright (C) 2008-2012 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_colored_usergroups
 */

if (!defined('FORUM')) die();

function cache_pun_coloured_usergroups()
{
	global $forum_db;

	$query = array(
		'SELECT'	=> 'g.g_id, g.link_color, g.hover_color',
		'FROM'		=> 'groups AS g'
	);

	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$output = array();

	while ($all_groups = $forum_db->fetch_assoc($result))
	{
		if (isset($all_groups['link_color']))
		{
			$link_color = ' color: '.$all_groups['link_color'].';';
			$output[] = '.group_color_'.$all_groups['g_id'].' a:link, .group_color_'.$all_groups['g_id'].' { color: '.$all_groups['link_color'].' !important; }'."\n";
			$output[] = '.group_color_'.$all_groups['g_id'].' a:visited { color: '.$all_groups['link_color'].'; }'."\n";
		}
		else
		{
			$link_color='';
		}

		if (isset($all_groups['hover_color']))
		{
				$output[] = '.group_color_'.$all_groups['g_id'].' a:hover { color: '.$all_groups['hover_color'].'; }'."\n";
				$output[] = '.group_color_'.$all_groups['g_id'].' { color: '.$all_groups['hover_color'].'; }'."\n\n";
		};
	}


	// WRITE CACHE
	if (!empty($output))
	{
		if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
			require FORUM_ROOT.'include/cache.php';

		if (!write_cache_file(FORUM_CACHE_DIR.'cache_pun_coloured_usergroups.php', '<?php'."\n\n".'$pun_colored_usergroups_cache = \''.implode(" ",$output)."';\n".'?>'))
		{
			error('Unable to write configuration cache file to cache directory. Please make sure PHP has write access to the directory \'cache\'.', __FILE__, __LINE__);
		}
	}
}

define('CACHE_PUN_COLOURED_USERGROUPS_LOADED', 1);
