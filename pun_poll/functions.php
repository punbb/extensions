<?php

class Pun_poll {

	public static function show_form($question, $poll_answers, $options_count, $days_poll, $votes_poll, $read_unvote_users, $revote, $show_edit_poll_options = false) {
		global $forum_user, $lang_pun_poll, $forum_config, $forum_page;

		// With Questions always keep poll open
		$poll_switcher_block_visible = true;
		$poll_block_open = (isset($_POST['pun_poll_block_open']) && $_POST['pun_poll_block_open'] == '1') ? true : false;
		if (!empty($question)) {
			$poll_block_open = true;

			// When edit existing topic poll - always show block and hide switcher
			if (defined('FORUM_PAGE') && FORUM_PAGE == 'postedit') {
				$poll_switcher_block_visible = false;
			}
		}


	?>
		<fieldset id="pun_poll_switcher_block" class="frm-group group<?php echo ++$forum_page['group_count']; echo ((!$poll_switcher_block_visible) ? ' hidden' : ''); ?>">
			<span class="js_link" id="pun_poll_switcher_link" data-lang-hide="<?php echo forum_htmlencode($lang_pun_poll['Hide poll']); ?>" data-lang-show="<?php echo forum_htmlencode($lang_pun_poll['Show poll']); ?>"><?php echo forum_htmlencode((!$poll_block_open) ? $lang_pun_poll['Show poll'] : $lang_pun_poll['Hide poll']); ?></span>
		</fieldset>

	<div id="pun_poll_form_block" style="display: <?php echo (($poll_block_open) ? 'block' : 'none'); ?>;">
		<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
			<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="sf-box text">
					<label for="fld<?php echo ++$forum_page['fld_count'] ?>">
						<span>
							<?php echo $lang_pun_poll['Poll question'] ?>
						</span>
						<small><?php echo $lang_pun_poll['Question len limit'] ?></small>
					</label>
					<br/>
					<span class="fld-input">
						<input type="text" id="quest" name="question_of_poll" size="70" maxlength="150"  value="<?php echo forum_htmlencode($question); ?>">
					</span>
				</div>
			</div>
			<?php
			// Validate of pull_answers
			if ($poll_answers != null) {
				for ($ans_num = 0; $ans_num < count($poll_answers); $ans_num++) {
					$poll_answers[$ans_num] = forum_trim($poll_answers[$ans_num]);
				}

				$poll_answers = array_unique($poll_answers);
			}

			for ($opt_num = 0; $opt_num < $options_count; $opt_num++):
			?>
				<div class="sf-set set<?php echo ++$forum_page['item_count']; echo (($opt_num === 0) ? ' prepend-top' : '');  ?>" data-item-count="<?php echo $forum_page['item_count'] ?>" data-fld-count="<?php echo ++$forum_page['fld_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo $forum_page['fld_count'] ?>">
							<span><?php echo $lang_pun_poll['Voting answer'] ?></span>
						</label>
						<br/>
						<span class="fld-input">
							<input id="fld<?php echo $forum_page['fld_count'] ?>" type="text" name="poll_answer[]" size="35" maxlength="70" value="<?php echo ($poll_answers != null && isset($poll_answers[$opt_num]) ? forum_htmlencode($poll_answers[$opt_num]) : '') ?>"/>
						</span>
					</div>
				</div>
			<?php
			endfor;
			?>
			<!-- Template for add_poll_options -->
			<div id="pun_poll_add_option_template" class="hidden">
				<div class="sf-box text">
					<label for="">
						<span><?php echo $lang_pun_poll['Voting answer'] ?></span>
					</label>
					<br/>
					<span class="fld-input">
						<input id="fld" type="text" name="poll_answer[]" size="35" maxlength="70" value=""/>
					</span>
				</div>
			</div>

			<span class="js_link" id="pun_poll_add_options_link"><?php echo $lang_pun_poll['Add poll option']; ?></span>
		</fieldset>

		<fieldset id="pun_poll_update_block" class="hidden frm-group frm-hdgroup group<?php echo ++$forum_page['group_count'] ?>">
			<fieldset class="mf-set set<?php echo ++$forum_page['item_count'] ?> mf-head">
				<legend>
					<span><?php echo $lang_pun_poll['Summary count'] ?></span>
				</legend>
				<div class="mf-box">
					<div class="mf-field mf-field1">
						<span class="fld-input">
							<input id="fld<?php echo ++$forum_page['fld_count'] ?>" type="text" name="poll_ans_count" size="5" maxlength="5" value="<?php echo $options_count ?>">
						</span>
					</div>
					<div class="mf-field">
						<span class="submit">
							<input type="submit" name="update_poll" value="<?php echo $lang_pun_poll['Button note'] ?>" formnovalidate />
						</span>
					</div>
				</div>
			</fieldset>
		</fieldset>

		<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
			<?php if ($forum_config['p_pun_poll_enable_read']): ?>
			<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="sf-box checkbox">
					<span class="fld-input">
						<input type="checkbox" id="first_option" value="1" name="read_unvote_users" <?php echo isset($_POST['read_unvote_users']) || $read_unvote_users ? 'checked' : '' ?>/>
					</span>
					<label for="fld<?php echo $forum_page['fld_count'] ?>">
						<span><?php echo $lang_pun_poll['Show poll res'] ?></span>
						<?php echo $lang_pun_poll['Disable see results info'] ?>
					</label>
				</div>
			</div>
			<?php endif; if ($forum_config['p_pun_poll_enable_revote']): ?>
			<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="sf-box checkbox">
					<span class="fld-input">
						<input type="checkbox" id="second_option" value="1" name="revouting" <?php echo isset($_POST['revouting']) || $revote ? 'checked' : '' ?>/>
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
						<small><?php echo $lang_pun_poll['Days voting note']; ?></small>
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
						<small><?php echo $lang_pun_poll['Maximum votes note']; ?></small>
					</label>
					<br/>
					<span class="fld-input">
						<input id="fld<?php echo $forum_page['fld_count'] ?>" type="text" name="allow_poll_votes" size="5" maxlength="5" value="<?php echo ($votes_poll == null) ? ('') : ($votes_poll); ?>">
					</span>
				</div>
			</div>
		</fieldset>

		<?php if ($show_edit_poll_options): ?>
		<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
			<div class="sf-set set1">
				<div class="sf-box checkbox">
					<span class="fld-input">
						<input id="fld<?php echo ++ $forum_page['fld_count'] ?>" type="checkbox" value="1" name="reset_poll"/>
					</span>
					<label for="fld<?php echo $forum_page['fld_count'] ?>">
						<?php echo $lang_pun_poll['Reset res notice'] ?>
					</label>
				</div>
			</div>
			<div class="sf-set set2">
				<div class="sf-box checkbox">
					<span class="fld-input">
						<input id="fld<?php echo ++ $forum_page['fld_count'] ?>" type="checkbox" value="1" name="remove_poll"/>
					</span>
					<label for="fld<?php echo $forum_page['fld_count'] ?>">
						<?php echo $lang_pun_poll['Remove v notice'] ?>
					</label>
				</div>
			</div>
		</fieldset>
		<?php endif; ?>

	</div>
	<?php
	}


	// Add poll to DB
	public static function add_poll($topic_id, $question, $poll_answers, $days_poll, $votes_poll, $read_unvote_users, $revote) {
		global $forum_db;

		$query = array(
			'INSERT'	=>	'topic_id, question, read_unvote_users, revote, created, days_count, votes_count',
			'INTO'		=>	'questions',
			'VALUES'	=>	$topic_id.', \''.$forum_db->escape($question).'\', '.$read_unvote_users.', '.$revote.', '.time().', '.$days_poll.', '.$votes_poll
		);
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		foreach ($poll_answers as $answer)
		{
			$query = array(
				'INSERT'	=>	'topic_id, answer',
				'INTO'		=>	'answers',
				'VALUES'	=>	$topic_id.', \''.$forum_db->escape($answer).'\''
			);

			$forum_db->query_build($query) or error(__FILE__, __LINE__);
		}
	}


	// Remove poll from DB
	public static function remove_poll($topic_id) {
		global $forum_db;

		$topic_id = intval($topic_id);
		if ($topic_id > 0) {
			// Remove questions
			$query = array(
				'DELETE'	=> 'questions',
				'WHERE'		=> 'topic_id='.$topic_id
			);
			$forum_db->query_build($query) or error(__FILE__, __LINE__);

			// Remove answers
			$query = array(
				'DELETE'	=> 'answers',
				'WHERE'		=> 'topic_id='.$topic_id
			);
			$forum_db->query_build($query) or error(__FILE__, __LINE__);

			// Remove voting results
			$query = array(
				'DELETE'	=> 'voting',
				'WHERE'		=> 'topic_id='.$topic_id
			);
			$forum_db->query_build($query) or error(__FILE__, __LINE__);
		}
	}


	// Update poll in DB
	public static function update_poll($topic_id, $question, $poll_answers, $days_poll, $votes_poll, $read_unvote_users, $revote, $old_question, $old_poll_answers, $old_days_poll, $old_votes_poll, $old_read_unvote_users, $old_revote) {
		global $forum_db;

		$question_update_set = array();
		if ($question != $old_question)
			$question_update_set[] = 'question = \''.$forum_db->escape($question).'\'';
		if ($days_poll != $old_days_poll)
			$question_update_set[] = 'days_count = '.(empty($days_poll) ? 'NULL' : $days_poll);
		if ($votes_poll != $old_votes_poll)
			$question_update_set[] = 'votes_count = '.(empty($votes_poll) ? 'NULL' : $votes_poll);
		if ($read_unvote_users != $old_read_unvote_users)
			$question_update_set[] = 'read_unvote_users = '.$read_unvote_users;
		if ($revote != $old_revote)
			$question_update_set[] = 'revote = '.$revote;

		if (!empty($question_update_set))
		{
			$poll_query = array(
				'UPDATE'	=>	'questions',
				'SET'		=>	implode(',', $question_update_set),
				'WHERE'		=>	'topic_id = '.$topic_id
			);

			$forum_db->query_build($poll_query) or error(__FILE__, __LINE__);
		}

		$poll_query = array(
			'SELECT'	=>	'id',
			'FROM'		=>	'answers',
			'WHERE'		=>	'topic_id = '.$topic_id,
			'ORDER BY'	=>	'id ASC'
		);
		$pun_poll_results = $forum_db->query_build($poll_query);
		$ans_ids = array();
		while ($answer = $forum_db->fetch_assoc($pun_poll_results))
			$ans_ids[] = $answer['id'];

		for ($ans_num = 0; $ans_num < count($poll_answers); $ans_num++)
		{
			if (isset($ans_ids[$ans_num]))
			{
				$query_pun_poll = array(
					'UPDATE'	=>	'answers',
					'SET'		=>	'answer = \''.$forum_db->escape($poll_answers[$ans_num]).'\'',
					'WHERE'		=>	'id = '.$ans_ids[$ans_num]
				);

				$forum_db->query_build($query_pun_poll) or error(__FILE__, __LINE__);
			}
			else
			{
				//New answer
				$query_pun_poll = array(
					'INSERT'	=>	'topic_id, answer',
					'INTO'		=>	'answers',
					'VALUES'	=>	$topic_id.', \''.$forum_db->escape($poll_answers[$ans_num]).'\''
				);

				$forum_db->query_build($query_pun_poll) or error(__FILE__, __LINE__);
			}
		}

		if (count($ans_ids) > count($poll_answers))
		{
			$ids = implode(',', array_slice($ans_ids, count($poll_answers)));
			$query_pun_poll = array(
				'DELETE'	=>	'answers',
				'WHERE'		=>	'id IN ('.$ids .')'
			);
			$forum_db->query_build($query_pun_poll) or error(__FILE__, __LINE__);

			$query_pun_poll = array(
				'DELETE'	=>	'voting',
				'WHERE'		=>	'answer_id IN ('.$ids.')'
			);
			$forum_db->query_build($query_pun_poll) or error(__FILE__, __LINE__);
		}
	}


	//
	public static function reset_poll($topic_id) {
		global $forum_db;

		$topic_id = intval($topic_id);
		if ($topic_id > 0) {
			// Remove voting results
			$query_pun_poll = array(
				'DELETE'	=>	'voting',
				'WHERE'		=>	'topic_id='.$topic_id
			);
			$forum_db->query_build($query_pun_poll) or error(__FILE__, __LINE__);
		}
	}


	//
	public static function poll_preview($question, $answers) {
		global $lang_pun_poll;

		$poll = '
				<div class="pun_poll_item unvotted">
					<div class="pun_poll_header">'.forum_htmlencode($question).'</div>
					<div class="main-frm">
						<fieldset class="frm-group group1">
							<fieldset class="mf-set set1">
								<legend><span>'.$lang_pun_poll['Options'].'</span></legend>
								<div class="mf-box">';

		for ($iter = 0; $iter < count($answers); $iter++) {
			$poll .= '
				<div class="mf-item pun_poll_answer_block" data-num="'.$iter.'">
				<span class="fld-input">
					<input id="fld'.$iter.'" type="radio" value="0" name="answer" />
				</span>
				<label for="fld'.$iter.'">'.forum_htmlencode($answers[$iter]).'</label>
			</div>';
		}

		$poll .= '
								</div>
							</fieldset>
						</fieldset>
					</div>
				</div>';

		return $poll;
	}


	// Validate poll data
	public static function data_validation($question, &$poll_answers, &$poll_days, &$poll_votes, $read_unvote_users, $revote) {
		global $lang_pun_poll, $lang_common, $forum_config, $errors;

		$errors = array();
		if (empty($question))
			$errors[] = $lang_pun_poll['Empty question'];

		if (empty($poll_answers) || !is_array($poll_answers))
			$errors[] = $lang_pun_poll['Empty answers'];

		$answers = array();
		foreach ($poll_answers as $answer)
		{
			$ans = forum_trim($answer);
			if (!empty($ans))
				$answers[] = $ans;
		}

		if (!empty($answers))
			$answers = array_unique($answers);

		$poll_answers = $answers;

		if (count($poll_answers) < 2)
			$errors[] = $lang_pun_poll['Min cnt options'];

		if (count($poll_answers) > $forum_config['p_pun_poll_max_answers'])
			$errors[] = sprintf($lang_pun_poll['Max cnt options'], $forum_config['p_pun_poll_max_answers']);

		if ($poll_days !== FALSE && $poll_votes !== FALSE)
			$errors[] = $lang_pun_poll['Days, votes count'];
		else if ($poll_days !== FALSE)
		{
			$poll_days = intval($poll_days) > 0 ? intval($poll_days) : FALSE;
			if (!$poll_days || $poll_days > 90)
				$errors[] = $lang_pun_poll['Days limit'];
		}
		else if ($poll_votes !== FALSE)
		{
			$poll_votes = intval($poll_votes) > 0 ? intval($poll_votes) : FALSE;
			if (!$poll_votes || $poll_votes > 500)
				$errors[] = $lang_pun_poll['Votes count'];
		}

		if ($read_unvote_users !== FALSE)
		{
			if (!$forum_config['p_pun_poll_enable_read'] || ($read_unvote_users != 0 && $read_unvote_users != 1))
				message($lang_common['Bad request']);
		}

		if ($revote !== FALSE)
		{
			if (!$forum_config['p_pun_poll_enable_revote'] || ($revote != 0 && $revote != 1))
				message($lang_common['Bad request']);
		}
	}
}
