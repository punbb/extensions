<?php

/**
 * pun_admin_manage_extensions_improved: continue page
 *
 * @copyright Copyright (C) 2009 PunBB
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
	array($lang_admin_common['Forum administration'], forum_link($forum_url['admin_index']))
);

switch ($type)
{
	case 'enable':
		$forum_page['crumbs'][] = $lang_pun_man_ext_improved['Enable checked'];
		break;
	case 'disable':
		$forum_page['crumbs'][] = $lang_pun_man_ext_improved['Disable checked'];
		break;
	case 'uninstall':
		$forum_page['crumbs'][] = $lang_pun_man_ext_improved['Uninstall checked'];
		break;
	default:
		$forum_page['crumbs'][] = isset($head_notice) ? $head_notice : '';
}

$forum_page['form_action'] = isset($handle) ? $handle : $base_url.'/admin/extensions.php?section=manage&amp;multy&amp;'.$type.'_sel';

$forum_page['hidden_fields'] = array(
	'csrf_token'	=> '<input type="hidden" name="csrf_token" value="'.generate_form_token($forum_page['form_action']).'" />'
);
if (in_array($type, array('enable', 'disable', 'uninstall')) && isset($_POST['extens']))
	$forum_page['hidden_fields']['selected_extens'] = '<input type="hidden" name="selected_extens" value="'.implode(',', array_keys($_POST['extens'])).'"/>';

require FORUM_ROOT.'header.php';

// START SUBST - <!-- forum_main -->
ob_start();

?>

<div class="main-subhead">
	<h2 class="hn"><span><?php echo array_pop($forum_page['crumbs']); ?></span></h2>
</div>

	<div class="main-content main-frm">
		<div class="ct-box">
			<p class="warn">
			<?php

			if (isset($important_message))
				echo $important_message;
			else if (in_array($type, array('enable', 'disable', 'uninstall')))
				echo $lang_pun_man_ext_improved['Dependency error'];

			?></p>
		</div>
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo $forum_page['form_action']; ?>">
			<div class="hidden">
				<?php echo implode("\n\t\t\t\t", $forum_page['hidden_fields'])."\n" ?>
			</div>
			<?php if (!empty($dependencies_error)):	?>
			<div class="ct-box error-box">
				<h2 class="warn hn"><span><?php echo $lang_pun_man_ext_improved['Dep error message']; ?></span></h2>
				<ul class="error-list">
				<?php

				foreach ($dependencies_error as $dep => $main)
				{

				?>
					<li class="warn"><span><?php echo sprintf($lang_pun_man_ext_improved['Work dependencies'], $dep, implode(', ', $main)); ?></span></li>
				<?php

				}

				?>
				</ul>
			</div>
			<?php endif; if ($type != 'reinstall'): ?>
			<fieldset class="frm-group group1">
				<fieldset class="mf-set set1">
					<legend><span><?php echo $lang_pun_man_ext_improved['Choose action'] ?></span></legend>
					<div class="mf-box">
						<div class="mf-item">
							<span class="fld-input"><input type="radio" id="fld0" name="<?php echo $type; ?>_type" value="0" /></span>
							<label for="fld0"><?php echo $lang_pun_man_ext_improved['Ignore deps']; ?></label>
						</div>
						<div class="mf-item">
							<span class="fld-input"><input type="radio" id="fld1" name="<?php echo $type; ?>_type" value="1" checked="checked" /></span>
							<label for="fld1"><?php if ($type == 'disable') echo $lang_pun_man_ext_improved['Disable deps extensions']; if ($type == 'enable') echo $lang_pun_man_ext_improved['Enable main']; if ($type == 'uninstall') echo $lang_pun_man_ext_improved['Disable deps extensions']?></label>
						</div>
						<?php if ($type == 'uninstall'): ?>
						<div class="mf-item">
							<span class="fld-input"><input type="radio" id="fld1" name="<?php echo $type; ?>_type" value="2" /></span>
							<label for="fld1"><?php echo $lang_pun_man_ext_improved['Uninstall all']; ?></label>
						</div>
						<?php endif; ?>
					</div>
				</fieldset>
			</fieldset>
			<?php endif; ?>
			<div class="frm-buttons">
				<span class="submit">
					<input type="submit" name="<?php echo $type; ?>_continue" value="Continue" />
				</span>
				<span class="cancel">
					<input type="submit" name="<?php echo $type; ?>_cancel" value="Cancel" />
				</span>
			</div>
		</form>
	</div>

<?php

$tpl_temp = trim(ob_get_contents());
$tpl_main = str_replace('<!-- forum_main -->', $tpl_temp, $tpl_main);
ob_end_clean();

require FORUM_ROOT.'footer.php';

?>