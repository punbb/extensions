<?php

function form_of_poll($question, $poll_answers, $options_count, $days_poll, $votes_poll)
{
	global $forum_user, $lang_pun_poll, $forum_config, $forum_page;

?>
    <fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
        <div class="sf-set set<?php echo ++$forum_page['item_count'] ?>"">
            <div class="sf-box text">
                <label for="fld<?php echo ++$forum_page['fld_count'] ?>">
                    <span>
                        <?php echo $lang_pun_poll['Poll question'] ?>
                    </span>
                </label>
                <br/>
                <span class="fld-input">
                    <input type="text" id="quest" name="question_of_poll" size="80" maxlength="150"  value="<?php echo $question; ?>">
                </span>
            </div>
        </div>
        <?php

        //Validate of pull_answers
        if ($poll_answers != null)
        {
            for ($ans_num = 0; $ans_num < count($poll_answers); $ans_num++)
                $poll_answers[$ans_num] = forum_trim($poll_answers[$ans_num]);
            $poll_answers = array_unique($poll_answers);
        }

        for ($opt_num = 0; $opt_num < $options_count; $opt_num++)
        {

        ?>
            <div class="sf-set set<?php echo ++$forum_page['item_count'] ?>"">
                <div class="sf-box text">
                    <label for="fld<?php echo ++$forum_page['fld_count'] ?>">
                        <span>
                            <?php echo $lang_pun_poll['Voting answer'] ?>
                        </span>
                    </label>
                    <br/>
                    <span class="fld-input">
                        <input id="fld<?php echo $forum_page['fld_count'] ?>" type="text" name="poll_answer[]" size="80" maxlength="70" value="<?php echo ($poll_answers != null && isset($poll_answers[$opt_num]) ? forum_htmlencode($poll_answers[$opt_num]) : '') ?>">
                    </span>
                </div>
            </div>
        <?php

        }

        ?>
    </fieldset>
    <fieldset class="frm-group frm-hdgroup group<?php echo ++$forum_page['group_count'] ?>">
        <fieldset class="mf-set set<?php echo ++$forum_page['item_count'] ?> mf-head">
            <legend>
                <span><?php echo $lang_pun_poll['Summary count'] ?></span>
            </legend>
            <div class="mf-box">
                <div class="mf-field mf-field1">
                    <label for="fld1">
                        <span class="fld-label"><?php echo $lang_pun_poll['Count']; ?></span>
                    </label>
                    <br/>
                    <span class="fld-input">
                        <input id="fld<?php echo ++$forum_page['fld_count'] ?>" type="text" name="poll_ans_count" size="5" maxlength="5" value="<?php echo $options_count ?>">
                    </span>
                </div>
                <div class="mf-field">
                    <span class="submit">
                        <input type="submit" name="update_poll" value="<?php echo $lang_pun_poll['Button note'] ?>">
                    </span>
                </div>
            </div>                
        </fieldset>
    </fieldset>
    <fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
        <?php if ($forum_config['p_pun_poll_enable_read']): ?>
        <div class="sf-set set<?php echo ++$forum_page['item_count'] ?>"">
            <div class="sf-box checkbox">
                <span class="fld-input">
                    <input type="checkbox" id="first_option" value="1" name="read_unvote_users" <?php echo isset($_POST['read_unvote_users']) ? 'checked' : '' ?>/>
                </span>
                <label for="fld<?php echo $forum_page['fld_count'] ?>">
                    <span><?php echo $lang_pun_poll['Show poll res'] ?></span>
                    <?php echo $lang_pun_poll['See results'] ?>
                </label>
            </div>
        </div>
        <?php endif; ?>
        <?php if ($forum_config['p_pun_poll_enable_revote']): ?>
        <div class="sf-set set<?php echo ++$forum_page['item_count'] ?>"">
            <div class="sf-box checkbox">
                <span class="fld-input">
                    <input type="checkbox" id="second_option" value="1" name="revouting" <?php echo isset($_POST['revouting']) ? 'checked' : '' ?>/>
                </span>
                <label for="fld<?php echo $forum_page['fld_count'] ?>">
                    <span><?php echo $lang_pun_poll['All ch vote'] ?></span>
                    <?php echo $lang_pun_poll['Able revote'] ?>
                </label>
            </div>
        </div>
        <?php endif; ?>
        <div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
            <div class="sf-box text">
                <label for="fld<?php echo ++$forum_page['fld_count'] ?>">
                    <span>
                        <?php echo $lang_pun_poll['Allow days'] ?>
                    </span>
                </label>
                <br/>
                <span class="fld-input">
                    <input id="fld<?php echo $forum_page['fld_count'] ?>" type="text" name="allow_poll_days" size="5" maxlength="5" value="<?php echo $days_poll; ?>">
                </span>
            </div>
        </div>
        <div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
            <div class="sf-box text">
                <label for="fld<?php echo ++$forum_page['fld_count'] ?>">
                    <span>
                        <?php echo $lang_pun_poll['Votes needed'] ?>
                    </span>
                </label>
                <br/>
                <span class="fld-input">
                    <input id="fld<?php echo $forum_page['fld_count'] ?>" type="text" name="allow_poll_votes" size="5" maxlength="5" value="<?php echo ($votes_poll == null) ? ('') : ($votes_poll); ?>">
                </span>
            </div>
        </div>
    </fieldset>
<?php

}
