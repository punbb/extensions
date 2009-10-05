<?php

/**
 * pun_cool_avatars page effects block
 *
 * @copyright (C) 2009 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_cool_avatars
 */

 ?>
	<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo $forum_page['form_action']; ?>" enctype="multipart/form-data">
		<div class="hidden">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token($forum_page['form_action']) ?>" />
		</div>
		<?php

		if (!$pho_to_templates['AET']['error']):

		?>
		<div class="content-head">
			<h2 class="hn"><span><?php echo $lang_pun_cool_avatars['Templates desc'] ?></span></h2>
		</div>
		<div class="sf-set set1">
			<div class="sf-box select">
				<label for="fld3"><span>Animated effects</span></label><br/>
				<span class="fld-input">
					<select name="template" id="template">
					<?php

					foreach ($pho_to_templates['AET']['templates'] as $group => $template_list)
					{

					?>
					<optgroup label="<?php echo forum_htmlencode($group); ?>">
					<?php foreach ($template_list as $template) { ?>
						<option value="<?php echo $template ?>"><?php echo $template ?></option>
					<?php } ?>
					</optgroup>
					<?php

					}

					?>
					</select>
				</span>
			</div>
		</div>
		<?php endif; ?>
		<div class="frm-buttons">
			<span class="submit"><input type="submit" name="apply_effect" value="<?php echo $lang_pun_cool_avatars['Apply effect'] ?>" /></span>
		</div>
	</form>
