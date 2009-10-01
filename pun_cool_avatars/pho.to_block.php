<?php

/**
 * pun_cool_avatars block in user profile
 *
 * @copyright (C) 2009 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_cool_avatars
 */

 ?>
<div class="main-content main-frm">
	<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo $forum_page['form_action']; ?>" enctype="multipart/form-data">
		<div class="hidden">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token($forum_page['form_action']) ?>" />
		</div>
		<?php

		$forum_page['item_count'] = $forum_page['fld_count'] = 0;
		if (!$pho_to_templates['AET']['error']):

		?>
		<div class="content-head">
			<h2 class="hn">
				<span><?php echo 'Some description' ?></span>
			</h2>
		</div>
		<fieldset class="frm-group group1">
			<?php

			foreach ($pho_to_templates['AET']['templates'] as $group => $template_list)
			{

			?>
			<fieldset class="mf-set set<?php echo ++$forum_page['item_count'] ?>">
				<legend><span><?php echo forum_htmlencode($group); ?></span></legend>
				<div class="mf-box">
				<?php

				foreach ($template_list as $template)
				{

				?>
					<div class="mf-item">
						<span class="fld-input">
							<input id="fld<?php echo ++$forum_page['fld_count'] ?>" type="radio" value="<?php echo forum_htmlencode($template); ?>" name="template"/>
						</span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo forum_htmlencode($template); ?></span></label>
					</div>
				<?php

				}
				$forum_page['group_count']++;

				?>
				</div>
			</fieldset>
			<?php

			}

			?>
		</fieldset>
		<?php endif; ?>
		<div class="frm-buttons">
			<span class="submit"><input type="submit" name="apply_effect" value="<?php echo 'Apply effect' ?>" /></span>
		</div>
	</form>
</div>