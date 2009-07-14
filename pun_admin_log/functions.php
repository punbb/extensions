<?
/***********************************************************************

	Copyright (C) 2008-2009 PunBB

	PunBB is free software; you can redistribute it and/or modify it
	under the terms of the GNU General Public License as published
	by the Free Software Foundation; either version 2 of the License,
	or (at your option) any later version.

	PunBB is distributed in the hope that it will be useful, but
	WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston,
	MA  02111-1307  USA

***********************************************************************/

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