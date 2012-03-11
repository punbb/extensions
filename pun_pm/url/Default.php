<?php
/**
 * Default SEF URL scheme.
 *
 * @copyright (C) 2008-2012 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_pm
 */

$forum_url['pun_pm_send'] = 'misc.php?action=pun_pm_send';
$forum_url['pun_pm'] = 'misc.php?section=pun_pm';
$forum_url['pun_pm_inbox'] = 'misc.php?section=pun_pm&amp;pmpage=inbox';
$forum_url['pun_pm_outbox'] = 'misc.php?section=pun_pm&amp;pmpage=outbox';
$forum_url['pun_pm_write'] = 'misc.php?section=pun_pm&amp;pmpage=write';
$forum_url['pun_pm_edit'] = 'misc.php?section=pun_pm&amp;pmpage=write&amp;message_id=$1';
$forum_url['pun_pm_view'] = 'misc.php?section=pun_pm&amp;pmpage=$2&amp;message_id=$1';
$forum_url['pun_pm_post_link'] = 'misc.php?section=pun_pm&amp;pmpage=compose&amp;receiver_id=$1';

?>