<?php

/**
 * pun_stop_bots view of questions management page
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_stop_bots
 */

?>
	<div class="main-subhead">
		<h2 class="hn"><span><?php echo $lang_pun_stop_bots['Management subhead']; ?></span></h2>
	</div>
    <div class="main-content main-frm">
        <form action="<?php echo $forum_page['form_action'] ?>" accept-charset="utf-8" method="post" class="frm-form">
            <div class="hidden">
                <input type="hidden" value="<?php echo $forum_page['csrf_token'] ?>" name="csrf_token" />
            </div>
            <div id="info-censored-intro" class="ct-box">
                <p><?php echo $lang_pun_stop_bots['Management notice']; ?></p>
            </div>
            <fieldset class="frm-group frm-hdgroup group1">
                <legend class="group-legend"><span>Add word</span></legend>
                <fieldset class="mf-set set1 mf-head">
                    <legend><span><?php echo $lang_pun_stop_bots['Management add question']; ?></span></legend>
                    <div class="mf-box">
                        <div class="mf-field mf-field1">
                            <label for="fld1"><span class="fld-label"><?php echo $lang_pun_stop_bots['Management question']; ?></span></label><br/>
                            <span class="fld-input"><input type="text" maxlength="60" size="24" name="question" id="fld1"/></span>
                        </div>
                        <div class="mf-field">
                            <label for="fld2"><span class="fld-label"><?php echo $lang_pun_stop_bots['Management answers']; ?></span></label><br/>
                            <span class="fld-input"><input type="text" maxlength="60" size="24" name="answers" id="fld2"/></span>
                        </div>
                        <div class="mf-field">
                            <span class="submit"><input type="submit" value="<?php echo $lang_pun_stop_bots['Management btn add']; ?>" name="add_question"/></span>
                        </div>
                    </div>
                </fieldset>
            </fieldset>
        </form>
        <?php if (!empty($pun_stop_bots_questions)): ?>
        <form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo $forum_page['form_action'] ?>">
            <div class="hidden">
                <input type="hidden" name="csrf_token" value="<?php echo $forum_page['csrf_token'] ?>" />
            </div>
            <fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
                <legend class="group-legend"><span><?php echo $lang_pun_stop_bots['Management existing question legend'] ?></span></legend>
                <?php

                foreach ($pun_stop_bots_questions['question'] as $stop_bots_question)
				{

				?>	
				<fieldset class="mf-set mf-extra set<?php echo ++$forum_page['item_count'] ?><?php echo ($forum_page['item_count'] == 1) ? ' mf-head' : ' mf-extra' ?>">
					<legend><span><?php echo $lang_pun_stop_bots['Management existing question'] ?></span></legend>
					<div class="mf-box">
						<div class="mf-field mf-field1">
							<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_pun_stop_bots['Question'] ?></span></label><br />
							<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="question[<?php echo $stop_bots_question['id'] ?>]" value="<?php echo forum_htmlencode($stop_bots_question['question']) ?>" size="24" maxlength="60" /></span>
						</div>
						<div class="mf-field">
							<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_pun_stop_bots['Answers'] ?></span></label><br />
							<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="answers[<?php echo $stop_bots_question['id'] ?>]" value="<?php echo forum_htmlencode($stop_bots_question['answers']) ?>" size="24" maxlength="60" /></span>
						</div>
						<div class="mf-field">
							<span class="submit"><input type="submit" name="update[<?php echo $stop_bots_question['id'] ?>]" value="<?php echo $lang_pun_stop_bots['Management btn update'] ?>" /> <input type="submit" name="remove[<?php echo $stop_bots_question['id'] ?>]" value="<?php echo $lang_pun_stop_bots['Management btn remove'] ?>" /></span>
						</div>
					</div>
				</fieldset>
				<?php

				}

				?>
			</fieldset>
		</form>
		<?php endif; ?>
    </div>