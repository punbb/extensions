<?php

/**
 * pun_funny_avatars page upload form block
 *
 * @copyright (C) 2009 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_funny_avatars
 */

	if (!empty($errors)):

?>
	<div class="ct-box error-box">
		<h2 class="warn hn"><?php echo $lang_pun_funny_avatars['Error warning']; ?></h2>
		<ul class="error-list">
		<?php foreach ($errors as $error) { ?>
			<li class="warn"><span><?php echo $error ?></span></li>
		<?php }?>
		</ul>
	</div>
	<?php else: ?>
	<div class="content-head">
		<h2 class="hn"><span><?php echo $lang_pun_funny_avatars['New avatar'] ?></span></h2>
	</div>
	<fieldset class="frm-group group1">
		<p><img src="<?php echo $forum_page['result_image_link']; ?>" /></p>
		<p><a href="<?php echo $forum_page['rewrite_avatar']; ?>"><?php echo $forum_page['rewrite_avatar_notice']; ?></a></p>
	</fieldset>
	<?php endif; ?>