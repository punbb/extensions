<?php

/**
 * File based SEF URL scheme.
 *
 * @copyright (C) 2008-2009 PunBB, partially based on code (C) 2008-2009 FluxBB.org
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_karma
 */

// Make sure no one attempts to run this script "directly"
if (!defined('FORUM'))
	exit;

$forum_url['karmaplus'] = 'post-$1-karmaplus-$2.html#p$1';
$forum_url['karmaminus'] = 'post-$1-karmaminus-$2.html#p$1';
$forum_url['karmacancel'] = 'post-$1-karmacancel-$2.html#p$1';