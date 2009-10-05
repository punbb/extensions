<?php

/**
 * pun_cool_avatars block in user profile
 *
 * @copyright (C) 2009 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_cool_avatars
 */
 
if (!empty($errors)):	
?>
	<div class="ct-box error-box">
		<h2 class="warn hn"><?php echo $lang_pun_cool_avatars['Error warning']; ?></h2>
		<ul class="error-list">
		<?php foreach ($errors as $error) { ?>
			<li class="warn"><span><?php echo $error ?></span></li>
		<?php }?>
		</ul>
	</div>
	<?php endif; ?>
	<div class="ct-box info-box">
		<a href="<?php echo $forum_page['uploaded_image_link'] ?>">Here</a> you can view you uploaded image. To remove it visit this <a href="<?php echo $forum_page['uploaded_image_remove'] ?>">link</a>.
	</div>

