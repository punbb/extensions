<?php
/**
 * Default SEF URL scheme.
 *
 * @copyright (C) 2008-2009 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_animated_avatars
 */

$forum_url['animated_avatar'] = 'profile.php?section=animated_avatar&amp;id=$1';
$forum_url['animated_avatar_remove_file'] = 'profile.php?section=animated_avatar&amp;id=$1&amp;remove_file&amp;csrf_token=$2';
$forum_url['animated_avatar_rewrite_avatar'] = 'profile.php?section=animated_avatar&amp;id=$1&amp;rewrite_avatar&amp;csrf_token=$2';
$forum_url['animated_avatar_request'] = 'profile.php?section=animated_avatar&amp;id=$1&amp;request_id=$2&amp;csrf_token=$3';

?>
