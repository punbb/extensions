<?php

function send_invitation()
{
    global $forum_db, $forum_user, $forum_url, $lang_common, $lang_inv_sys, $forum_config, $base_url, $forum_page, $cur_forum;

    $invite = isset($_GET['invite']) ? intval($_GET['invite']) : 0;
    if($invite)
    {

    }

}

function show_invitation_form()
{
    global $forum_page, $base_url, $inv_sys_url, $lang_common, $lang_inv_sys,$forum_user;

    $forum_page['form_action'] = $base_url.'/'.$inv_sys_url['Invite'];
    $forum_page['group_count'] =  $forum_page['item_count'] = $forum_page['fld_count']= 0;
    $forum_page['main_head'] = 'Invite new user';
    $forum_page['hidden_fields'] = array(
	'csrf_token'	=> '<input type="hidden" name="csrf_token" value="'.generate_form_token($forum_page['form_action']).'" />',
    );
    
    ?>

    <div class="main-head">
	<h2 class="hn"><span><?php echo $forum_page['main_head'] ?></span></h2>
    </div>
    <div class="main-content main-forum">
                <form id="afocus" class="frm-form" method="post" accept-charset="utf-8" action="<?php echo $forum_page['form_action'] ?>">
			<div class="hidden">
				<?php echo implode("\n\t\t\t\t", $forum_page['hidden_fields'])."\n" ?>
			</div>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text required longtext">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_inv_sys['Email'] ?>  <em><?php echo $lang_common['Required'] ?></em></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="req_subject" value="<?php echo(isset($_POST['req_subject']) ? forum_htmlencode($_POST['req_subject']) : '') ?>" size="35" maxlength="80" /></span>
					</div>
				</div>
			</fieldset>
			<div class="frm-buttons">
				<span class="submit"><input type="submit" name="submit" value="<?php echo $lang_common['Submit'] ?>" /></span>
				<span class="cancel"><input type="submit" name="cancel" value="<?php echo $lang_common['Cancel'] ?>" /></span>
			</div>
		</form>
    </div>
    <?php

}

?>
