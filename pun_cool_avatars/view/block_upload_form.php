<?php

/**
 * pun_cool_avatars page upload form block
 *
 * @copyright (C) 2009 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_cool_avatars
 */

	if (!empty($errors)):	
 ?>
	<div class="ct-box error-box">
		<h2 class="warn hn"><strong>Warning!</strong> The following errors must be corrected before your profile can be updated:</h2>
		<ul class="error-list">
        <?php foreach ($errors as $error) { ?>
			<li class="warn"><span><?php echo $error ?></span></li>
		<?php }?>
		</ul>
	</div>
    <?php endif; ?>
	<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo $forum_page['form_action'] ?>" enctype="multipart/form-data">
        <div class="hidden">
		    <input type="hidden" value="<?php echo $forum_config['o_pun_cool_avatars_max_size']; ?>" name="MAX_FILE_SIZE"/>
            <input type="hidden" name="csrf_token" value="<?php echo generate_form_token($forum_page['form_action']) ?>" />
        </div>    
        <div class="ct-box info-box">Use this form to upload photo to server</div>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_profile['Avatar'] ?></strong></legend>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text required">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_pun_cool_avatars['Upload photo'] ?></span></label></br>
						<span class="fld-input"><input id="fld<?php echo $forum_page['fld_count'] ?>" name="req_file" type="file" size="40" /></span>
					</div>
				</div>
			</fieldset>
			<div class="frm-buttons">
				<span class="submit"><input type="submit" name="photo_upload" value="<?php echo $lang_pun_cool_avatars['Upload'] ?>" /></span>
			</div>
		</form>