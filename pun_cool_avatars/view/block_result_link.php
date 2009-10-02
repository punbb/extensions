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
	<div>
    	<img src="<?php echo $forum_page['result_image_link'] ?>" /></br>
		<a href="<?php echo $forum_page['rewrite_avatar'] ?>">Replace or create avatar with this one.</a>
    </div>