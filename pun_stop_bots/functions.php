<?php

/**
 * pun_stop_bots functions file
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_stop_bots
 */

if (!defined('FORUM')) die();

function pun_stop_bots_generate_cache()
{
	global $forum_db;

	// Get the forum config from the DB
	$query = array(
		'SELECT'	=> 'id, question, answers',
		'FROM'		=> 'pun_stop_bots_questions'
	);
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$output = array();
	if ($forum_db->num_rows($result) > 0)
	{
		while ($cur_item = $forum_db->fetch_row($result))
		{
			$output['id'] = $cur_item['id'];
			$output['question'] = $cur_item['question'];
			$output['answers'] = $cur_item['answers'];
		}
	}

	// Output config as PHP code
	$fh = @fopen(FORUM_CACHE_DIR.'cache_pun_stop_bots.php', 'wb');
	if (!$fh)
		error('Unable to write configuration cache file to cache directory. Please make sure PHP has write access to the directory \'cache\'.', __FILE__, __LINE__);

	fwrite($fh, '<?php'."\n\n".'define(\'PUN_STOP_BOTS_CACHE_LOADED\', 1);'."\n\n".'$pun_stop_bots_questions = '.var_export($output, true).';'."\n\n".'?>');

	fclose($fh);
}

?>
