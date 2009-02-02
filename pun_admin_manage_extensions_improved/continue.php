<?php

define('FORUM_PAGE_SECTION', 'extensions');
define('FORUM_PAGE', 'admin-extensions-manage');

if (file_exists($ext_info['path'].'/lang/'.$forum_user['language'].'/'.$ext_info['id'].'.php'))
	require $ext_info['path'].'/lang/'.$forum_user['language'].'/'.$ext_info['id'].'.php';
else
	require $ext_info['path'].'/lang/English/'.$ext_info['id'].'.php';
		
// Setup breadcrumbs
$forum_page['crumbs'] = array(
	array($forum_config['o_board_title'], forum_link($forum_url['index'])),
	array($lang_admin['Forum administration'], forum_link($forum_url['admin_index'])),
	$head_notice
);		
require FORUM_ROOT.'header.php';	
		
// START SUBST - <!-- forum_main -->
ob_start();

?>
<div id="brd-main" class="main sectioned admin">

<?php echo generate_admin_menu(); ?>

	<div class="main-head">
		<h1><span>{ <?php echo end($forum_page['crumbs']) ?> }</span></h1>
	</div>
	<div class="main-content frm">
		<div class="frm-head">
			<h2><span><?php echo $lang_pun_man_ext_improved['Extension title'].'. '.$head_notice; ?></span></h2>
		</div>
		<div class="frm-info">
			<p class="warn"><?php echo $important_message; ?></p>
		</div>
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo $handle; ?>">
			<div class="hidden">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token($handle);  ?>" />
				<?php 
				
				if (isset($_POST['extens']))
					echo '<input type="hidden" name="selected_extens" value="'.implode(',', array_keys($_POST['extens'])).'"/>';
					
				?>
			</div>
			
			<fieldset class="frm-group">
				<?php echo $form_radboxes; ?>
			</fieldset>
			
			<div class="frm-buttons">
				<span class="submit"><input type="submit" name="<?php echo $type; ?>_continue" value="Continue" /></span>
				<span class="cancel"><input type="submit" name="<?php echo $type; ?>_cancel" value="Cancel" /></span>
			</div>
	 	</form>	
	</div>

</div>		
<?php		

		$tpl_temp = trim(ob_get_contents());
		$tpl_main = str_replace('<!-- forum_main -->', $tpl_temp, $tpl_main);
		ob_end_clean();
	
		require FORUM_ROOT.'footer.php';

?>

