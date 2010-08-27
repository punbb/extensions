<?php


function send_invitation()
{
	global $forum_db, $forum_user, $forum_url, $lang_common, $lang_inv_sys, $base_url, $forum_config, $base_url, $forum_page, $cur_forum, $ext_info,$inv_sys_url;
	require_once FORUM_ROOT.'include/email.php';

	if (isset($_POST['cancel']))
		redirect(forum_link($forum_url['index']), $lang_inv_sys['Cancel redirect']);

	$invite = isset($_GET['invite']) ? intval($_GET['invite']) : 0;
	if($invite)
	{
		$email = strtolower(forum_trim($_POST['req_email']));

		$personal_message=forum_trim($_POST['inv_message']);

		//Check if we've already sent invitation letter to this email
		$query=array(
			'SELECT'=>'invitee_email',
			'FROM'=>'pun_invitations_only',
			'WHERE' =>'invitee_email=\''.$forum_db->escape($email).'\''
		);
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		if ($forum_db->num_rows($result))
			message($lang_inv_sys['Duplicate email']);


		//Compose invitation email
		$invitee_code=random_key(8, true);
		$created= time();
		// Load the "invitation" template
		if($personal_message=='')
		{
			$mail_tpl = forum_trim(file_get_contents($ext_info['path'].'/lang/'.$forum_user['language'].'/mail_templates/invitation.tpl'));

			$first_crlf = strpos($mail_tpl, "\n");
			$mail_subject = forum_trim(substr($mail_tpl, 8, $first_crlf-8));
			$mail_message = forum_trim(substr($mail_tpl, $first_crlf));
			$mail_subject = str_replace('<board_title>', $forum_config['o_board_title'], $mail_subject);
			$mail_message = str_replace('<board_title>', $forum_config['o_board_title'], $mail_message);
			$mail_message = str_replace('<base_url>', $base_url.'/', $mail_message);
			$mail_message = str_replace('<email>',$email,$mail_message);
			$mail_message = str_replace('<username>', $forum_user['username'], $mail_message);
			$mail_message = str_replace('<invitation_url>', forum_link($inv_sys_url['Registration link'], $invitee_code), $mail_message);
			$mail_message = str_replace('<board_mailer>', sprintf($lang_common['Forum mailer'], $forum_config['o_board_title']), $mail_message);
		}
		else
		{
			$mail_tpl = forum_trim(file_get_contents($ext_info['path'].'/lang/'.$forum_user['language'].'/mail_templates/inv_personal.tpl'));

			$first_crlf = strpos($mail_tpl, "\n");
			$mail_subject = forum_trim(substr($mail_tpl, 8, $first_crlf-8));
			$mail_message = forum_trim(substr($mail_tpl, $first_crlf));
			$mail_subject = str_replace('<board_title>', $forum_config['o_board_title'], $mail_subject);
			$mail_message = str_replace('<board_title>', $forum_config['o_board_title'], $mail_message);
			$mail_message = str_replace('<base_url>', $base_url.'/', $mail_message);
			$mail_message = str_replace('<text>', $personal_message, $mail_message);
			$mail_message = str_replace('<invitation_url>', forum_link($inv_sys_url['Registration link'], $invitee_code), $mail_message);
			$mail_message = str_replace('<board_mailer>', sprintf($lang_common['Forum mailer'], $forum_config['o_board_title']), $mail_message);
		}
		//Add information about this invitation to the table
		$query = array(
		'INSERT'	=> 'inviter_id, invitee_code, invitee_email, created',
		'INTO'		=> 'pun_invitations_only',
		'VALUES'	=> '\''.$forum_user['id'].'\',\''.$forum_db->escape($invitee_code).'\',\''.$forum_db->escape($email).'\',\''.$forum_db->escape($created).'\''
		);
	$forum_db->query_build($query) or error(__FILE__, __LINE__);

		//Send mail
		forum_mail($email, $mail_subject, $mail_message);

		message($lang_inv_sys['Succesfully sent']);

	}


}

function show_invitation_form()
{
	global $forum_page, $base_url, $inv_sys_url, $lang_common, $lang_inv_sys,$forum_user;

	$forum_page['form_action'] = $base_url.'/'.$inv_sys_url['Invite'];
	$forum_page['group_count'] =  $forum_page['item_count'] = $forum_page['fld_count']= 0;
	$forum_page['main_head'] = $lang_inv_sys['Invite new user'];
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


			<div id="req-msg" class="req-warn ct-box error-box">
				<p class="important"><?php printf($lang_inv_sys['Required warn'], '<em>'.$lang_common['Required'].'</em>') ?></p>
			</div>
 
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text required longtext">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_inv_sys['Email'] ?>  <em><?php echo $lang_common['Required'] ?></em></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="req_email" value="<?php echo(isset($_POST['req_email']) ? forum_htmlencode($_POST['req_email']) : '') ?>" size="75" maxlength="70" /></span>
					</div>
				</div>
				<div class="txt-set set<?php echo ++$forum_page['item_count']?>">
					<div class="txt-box textarea required">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_inv_sys['Inv message'] ?></span></label>
							<div class="txt-input">
								<span class="fld-input">
									<textarea id="fld<?php echo $forum_page['fld_count'] ?>" cols="95" rows="7" name="inv_message"></textarea>
								</span>
							</div>
					</div>
				</div>
				<div class="txt-set set<?php echo ++$forum_page['item_count']?>">
					<label for="fld<?php echo $forum_page['fld_count'] ?>">
						<small><p><?php echo $lang_inv_sys['Inv mes description'] ?></p></small>
					</label>
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
