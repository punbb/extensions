<?php

/**
 * Main attachment settings
 *
 * @copyright Copyright (C) 2009-2012 PunBB, partially based on Attachment Mod by Frank Hagstrom
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_attachment
 */

if (!defined('FORUM')) die();

$names = explode(',', $forum_config['attach_icon_name']);
$icons = explode(',', $forum_config['attach_icon_extension']);

$missing_files = array();
$big_images = array();
$pun_attach_errors = array();

foreach ($names as $icon_name)
{
	if (!file_exists($ext_info['path'].'/img/'.$icon_name))
		$pun_attach_errors['missing_files'][] = '<li class="warn"><span>'.$forum_config['attach_icon_folder'].$icon_name.'</span></li>';
	else
	{
		list($width, $height,,) = getimagesize($ext_info['path'].'/img/'.$icon_name);

		if (($width > 20) || ($height > 20))
			$pun_attach_errors['big_images'][] = '<li class="warn"><span>'.$forum_config['attach_icon_folder'].$icon_name.'</span></li>';
	}
}

// Setup the form
$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;

// Setup breadcrumbs
$forum_page['crumbs'] = array(
	array($forum_config['o_board_title'], forum_link($forum_url['index'])),
	array($lang_admin_common['Forum administration'], forum_link($forum_url['admin_index'])),
	array($lang_admin_common['Settings'], forum_link($forum_url['admin_settings_setup'])),
	array($lang_attach['Attachment'], forum_link($attach_url['admin_options_attach']))
);

define('FORUM_PAGE_SECTION', 'settings');
define('FORUM_PAGE', 'admin-options-attach');
require FORUM_ROOT.'header.php';

// START SUBST - <!-- forum_main -->
ob_start();

?>
<div class="main-content main-frm">
	<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo forum_link($attach_url['admin_options_attach']) ?>">
		<div class="content-head">
			<h2 class="hn"><span><?php echo $lang_attach['Main options'] ?></span></h2>
		</div>
		<div class="hidden">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token(forum_link($attach_url['admin_options_attach'])) ?>" />
			<input type="hidden" name="form_sent" value="1" />
		</div>
		<fieldset class="frm-group group1">
			<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="sf-box checkbox">
					<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count']; ?>" name="form[disable_attach]" value="1"<?php if ($forum_config['attach_disable_attach']) echo ' checked="checked"' ?> /></span>
					<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_attach['Disable attachments'] ?></span><?php echo $lang_attach['Disable attachments'] ?></label>
				</div>
			</div>
			<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="sf-box checkbox">
					<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count']; ?>" name="form[create_orphans]" value="1"<?php if ($forum_config['attach_create_orphans'] == '1') echo ' checked="checked"' ?> /></span>
					<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_attach['Create orphans'] ?></span><?php echo $lang_attach['Orphans help'] ?></label>
				</div>
			</div>
			<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="sf-box text">
					<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_attach['Always deny'] ?></span></label><br />
					<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[always_deny]" size="75" maxlength="150" value="<?php echo forum_htmlencode($forum_config['attach_always_deny']); ?>" /></span>
				</div>
			</div>
			<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="sf-box checkbox">
					<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[disp_small]" value="1"<?php if ($forum_config['attach_disp_small'] == '1') echo ' checked="checked"' ?> /></span>
					<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_attach['Display images'] ?></span><?php echo $lang_attach['Display small'] ?></label>
				</div>
			</div>
			<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="sf-box text">
					<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_attach['Max height'] ?></span></label><br />
					<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[small_height]" size="5" maxlength="5" value="<?php echo forum_htmlencode($forum_config['attach_small_height']); ?>" /></span>
				</div>
			</div>
			<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="sf-box text">
					<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_attach['Max width'] ?></span></label><br />
					<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[small_width]" size="5" maxlength="5" value="<?php echo forum_htmlencode($forum_config['attach_small_width']); ?>" /></span>
				</div>
			</div>
		</fieldset>
		<div class="content-head">
			<h2 class="hn"><span><?php echo $lang_attach['Manage icons'] ?></span></h2>
		</div>
		<div class="ct-box">
			<p><?php echo $lang_attach['Icons help'] ?></p>
		</div>
<?php if (!empty($pun_attach_errors['missing_files']) || !empty($pun_attach_errors['big_images'])): ?>
		<div class="ct-box error-box">
<?php

		if (!empty($pun_attach_errors['missing_files']))
		{

?>
			<h2 class="warn hn"><?php echo $lang_attach['Missing icons'] ?></h2>
			<ul class="error-list">
				<?php echo implode("\n\t\t\t\t", array_unique($pun_attach_errors['missing_files']))."\n" ?>
			</ul>
<?php

		}
		if (!empty($pun_attach_errors['big_images']))
		{

?>
			<h2 class="warn hn"><?php echo $lang_attach['Big icons'] ?></h2>
			<ul class="error-list">
				<?php echo implode("\n\t\t\t\t", array_unique($pun_attach_errors['big_images']))."\n" ?>
			</ul>
<?php

		}

?>
		</div>
<?php endif; ?>
		<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
			<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="sf-box checkbox">
					<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[use_icon]" value="1"<?php if ($forum_config['attach_use_icon'] == '1') echo ' checked="checked"' ?> /></span>
					<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_attach['Use icons'] ?></span><?php echo $lang_attach['Display icons'] ?></label>
				</div>
			</div>
<?php

			if (!empty($names) && !empty($icons))
			{
				for ($i = 0; $i < count($icons); $i++)
				{

?>
						<div class="sf-box text">
							<span class="fld-input">
								<input type="text" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="attach_ext_<?php echo $i ?>" size="10" maxlength="10" value="<?php echo (isset($_POST['attach_ext_'.$i]) ? forum_htmlencode($_POST['attach_ext_'.$i]) : forum_htmlencode($icons[$i])) ?>" />
								<input type="text" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="attach_ico_<?php echo $i ?>" size="25" maxlength="50" value="<?php echo (isset($_POST['attach_ico_'.$i]) ? forum_htmlencode($_POST['attach_ico_'.$i]) : forum_htmlencode($names[$i])) ?>" />
								<?php if (!in_array($forum_config['attach_icon_folder'].$names[$i], $big_images) && !in_array($forum_config['attach_icon_folder'].$names[$i], $missing_files)): ?>
								<span class="fld-input"><img src="<?php echo $forum_config['attach_icon_folder'].$names[$i]; ?>" alt="<?php echo forum_htmlencode($names[$i]); ?>" /></span>
								<?php endif; ?>
							</span>
						</div>

<?php

				}
			}

?>
				<div class="sf-box text">
					<span class="fld-input">
						<input type="text" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="add_field_icon" size="10" maxlength="10" />
						<input type="text" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="add_field_file" size="25" maxlength="50" />
					</span>
				</div>
			</fieldset>
			<div class="frm-buttons">
				<span class="submit"><input type="submit" name="update_settings" value="<?php echo $lang_admin_common['Save changes'] ?>" /></span>
			</div>
		</form>
	</div>
