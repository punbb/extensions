<?php

/**
 * pun_stop_bots view of question page
 *
 * @copyright (C) 2008-2011 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_stop_bots
 */

?>
<div class="main-head">
	<h2 class="hn"><span><?php echo $lang_pun_stop_bots['Stop bots question']; ?></span></h2>
</div>
<div class="main-subhead">
	<h2 class="hn"><span><?php echo $forum_page['question']; ?> </span></h2>
</div>
<div class="main-content main-frm">
	<form id="afocus" class="frm-form" method="post" accept-charset="utf-8" action="<?php echo $forum_page['form_handler']; ?>">
		<div class="hidden">
		<?php foreach ($forum_page['hidden_fields'] as $hidden_key => $hidden_value): ?>
			<input name="<?php echo forum_htmlencode($hidden_key); ?>" value="<?php echo forum_htmlencode($hidden_value); ?>" type="hidden">
		<?php endforeach; ?>
		</div>
		<div class="sf-set set1">
			<div class="sf-box text required">
				<label for="fld1"><span><?php echo $lang_pun_stop_bots['Your answer']; ?></span></label><br/>
				<span class="fld-input"><input type="text" value="" maxlength="255" size="35" name="pun_stop_bots_answer" id="fld1" required /></span>
			</div>
		</div>
		<div class="frm-buttons">
			<span class="submit primary"><input name="pun_stop_bots_submit" value="<?php echo $lang_pun_stop_bots['Answer']; ?>" type="submit"></span>
		</div>
	</form>
</div>
