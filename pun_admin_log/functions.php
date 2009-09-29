<?php

/**
 * pun_admin_log functions
 *
 * @copyright (C) 2008-2009 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_admin_log
 */

if (!defined('FORUM'))
	exit;

function pun_log_write_logfile( $str )
{
	global $forum_config;
	$hf = fopen($forum_config['o_pun_admin_path_log_file'], 'a+');

	if ($hf)
	{
		fwrite($hf, $str);
		fclose($hf);
	}
}

function record_log_file($action, $comment)
{
	global $forum_user;
	return '['.date('j-M-Y H:i:s').']'."\t[".$forum_user['id']."]\t".$forum_user['username']."\t".$action."\t".$comment."\n";
}

?>