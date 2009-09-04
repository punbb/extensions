<?php

/**
 * Default SEF URL scheme.
 *
 * @copyright (C) 2008-2009 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_karma
 */

// Make sure no one attempts to run this script "directly"
if (!defined('FORUM'))
	exit;

$forum_url['karmaplus'] = 'viewtopic.php?pid=$1&amp;karmaplus&amp;csrf_token=$2#p$1';
$forum_url['karmaminus'] = 'viewtopic.php?pid=$1&amp;karmaminus&amp;csrf_token=$2#p$1';
$forum_url['karmacancel'] = 'viewtopic.php?pid=$1&amp;karmacancel&amp;csrf_token=$2#p$1';