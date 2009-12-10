<?php

/**
 * pun_colored_usergroups functions file
 *
 * @copyright (C) 2008-2009 PunBB
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
		if(isset($all_groups['link_color']))
		{
				$output[] = '.group_color_'.$all_groups['g_id'].' a:link {color: '.$all_groups['link_color'].';}'."\n";
				$output[] = '.group_color_'.$all_groups['g_id'].' a:visited {color: '.$all_groups['link_color'].';}'."\n";
				$output[] = '#brd-main .group_color_'.$all_groups['g_id'].' {font-size:12px; position:static; visibility:visible; color: '.$all_groups['link_color'].';}'."\n";
		};

		if(isset($all_groups['hover_color']))
		{
				$output[] = '.group_color_'.$all_groups['g_id'].' a:hover {color: '.$all_groups['hover_color'].';}'."\n";
				$output[] = '.brd .group_color_'.$all_groups['g_id'].' {color: '.$all_groups['hover_color'].';}'."\n\n";
		};
	}
	echo '444444444f';
	if (!empty($output))
	{ 
	echo ('wefwef');
		$fh = @fopen(FORUM_CACHE_DIR.'cache_pun_coloured_usergroups.php', 'wb');
		if (!$fh)
			error('Unable to write configuration cache file to cache directory. Please make sure PHP has write access to the directory \'cache\'.', __FILE__, __LINE__);
	
		fwrite($fh, '<?php'."\n\n".'$pun_colored_usergroups_cache = \''.implode(" ",$output)."';\n".'?>');
	
		fclose($fh);
	}
}

?>