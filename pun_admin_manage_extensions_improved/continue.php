<?php

/**
 * pun_admin_manage_extensions_improved: continue page
 *
 * @copyright Copyright (C) 2008 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_admin_manage_extensions_improved
 */

define('FORUM_PAGE_SECTION', 'extensions');
define('FORUM_PAGE', 'admin-extensions-manage');

if (file_exists($ext_info['path'].'/lang/'.$forum_user['language'].'/'.$ext_info['id'].'.php'))
	require $ext_info['path'].'/lang/'.$forum_user['language'].'/'.$ext_info['id'].'.php';
else
	require $ext_info['path'].'/lang/English/'.$ext_info['id'].'.php';
		
// Setup breadcrumbs
$forum_page['crumbs'] = array(
	array($forum_config['o_board_title'], forum_link($forum_url['index'])),
	array($lang_admin_common['Forum administration'], forum_link($forum_url['admin_index'])),
	$head_notice
);

require FORUM_ROOT.'header.php';

// START SUBST - <!-- forum_main -->
ob_start();

?>

<div class="main-subhead">
	<h2 class="hn"><span><?php echo $head_notice; ?></span></h2>
</div>
<divclass="main-content">
	<div class="main-content main-frm">
		<div class="ct-box">
			<p class="warn"><?php echo $important_message; ?></p>
		</div>
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo $handle; ?>">
			<div class="hidden">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token($handle);  ?>" />
				<?php 

				if (isset($_POST['extens']))
					echo '<input type="hidden" name="selected_extens" value="'.implode(',', array_keys($_POST['extens'])).'"/>';

				?>
			</div>
			
			<fieldset class="frm-group group1">
				<div class="sf-set set1">
					<div class="sf-box select">
						<?php echo $form_radboxes; ?>
					</div>
				</div>
			</fieldset>
			
			<div class="frm-buttons">
				<span class="submit">
					<input type="submit" name="<?php echo $type; ?>_continue" value="Continue" />
				</span>
				<span class="cancel">
					<input type="submit" name="<?php echo $type; ?>_cancel" value="Cancel" />
				</span>
			</div>
		</form>

<?php

$tpl_temp = trim(ob_get_contents());
$tpl_main = str_replace('<!-- forum_main -->', $tpl_temp, $tpl_main);
ob_end_clean();

require FORUM_ROOT.'footer.php';

?>

