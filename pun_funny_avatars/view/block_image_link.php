<?php

/**
 * pun_funny_avatars block in user profile
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
<?php endif; ?>
	<div class="content-head">
		<h2 class="hn"><span><?php echo $lang_pun_funny_avatars['Uploaded image'] ?></span></h2>
	</div>
	<fieldset class="frm-group group1">
		<img alt="<?php echo $forum_page['uploaded_image_link'] ?>" height="<?php echo $forum_page['image_size']['height'] ?>" width="<?php echo ($forum_page['image_size']['width'] > 660) ? '100%' : $forum_page['image_size']['width'].'px'; ?>" src="<?php echo $forum_page['uploaded_image_link'] ?>"/>
		<p><a href="<?php echo $forum_page['uploaded_image_remove'] ?>"><?php echo $lang_pun_funny_avatars['Remove']; ?></a></p>
	</fieldset>