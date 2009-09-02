<?php
/**
 * Regular URL scheme.
 *
 * @copyright (C) 2008-2009 PunBB, partially based on code (C) 2008-2009 FluxBB.org
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package PunBB
 */


// Make sure no one attempts to run this script "directly"
if (!defined('FORUM'))
	exit;

$forum_url['karmaplus'] = 'viewtopic.php?pid=$1&amp;karmaplus#p$1';
$forum_url['karmaminus'] = 'viewtopic.php?pid=$1&amp;karmaminus#p$1';
$forum_url['karmacancel'] = 'viewtopic.php?pid=$1&amp;karmacancel#p$1';