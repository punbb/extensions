<?php

/**
 * pun_funny_avatars page effects block
 *
 * @copyright (C) 2009 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_funny_avatars
 */

if (!$funny_templates['FET']['error']): ?>

	<div class="content-head">
		<h2 class="hn"><span><?php echo $lang_pun_funny_avatars['Templates desc'] ?></span></h2>
	</div>
	<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo $forum_page['form_action']; ?>" enctype="multipart/form-data">
		<div class="hidden">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token($forum_page['form_action']) ?>" />
		</div>
		<fieldset class="frm-group frm-hdgroup group1">
				<div class="sf-set set1">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" value="1" name="auto_crop" id="fld1"/></span>
						<label for="fld6"><span><?php echo $lang_pun_funny_avatars['Autocrop portrait']; ?></span><?php echo $lang_pun_funny_avatars['Autocrop portrait label']; ?></label>
					</div>
				</div>
				<fieldset class="mf-set set1 mf-head">
					<legend><span><?php echo $lang_pun_funny_avatars['Collages']; ?></span></legend>
					<div class="mf-box">
						<div class="mf-field mf-field1 text">
							<span class="fld-input">
								<select name="fet_template" id="fet_template">
								<?php

								foreach ($funny_templates['FET']['templates'] as $group => $template_list)
								{

								?>
								<optgroup label="<?php echo forum_htmlencode($group); ?>">
								<?php foreach ($template_list as $template_index => $template_info) { ?>
									<option value="<?php echo $template_info['name'] ?>"><?php echo $template_info['title'] ?></option>
								<?php } ?>
								</optgroup>
								<?php

								}

								?>
								</select>
							</span>
						</div>
						<div class="mf-field text">
							<span class="submit"><input type="submit" value="<?php echo $lang_pun_funny_avatars['Apply effect'] ?>" name="fet_template_submit"/></span>
						</div>
					</div>
				</fieldset>
				<div class="ct-set set1">
					<div class="ct-box" id="div_fet">
					</div>
				</div>
			</fieldset>
	</form>
<?php endif; ?>