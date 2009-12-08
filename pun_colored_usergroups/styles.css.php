<?php
header('Content-type: text/css; charset: UTF-8');
define('FORUM_ROOT', '../../');
require FORUM_ROOT.'include/essentials.php';
require_once FORUM_CACHE_DIR.'cache_pun_coloured_usergroups.php';
echo $pun_colored_usergroups_cache
?>