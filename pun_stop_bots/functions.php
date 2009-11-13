<?php

/**
 * pun_stop_bots functions file
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_stop_bots
 */

if (!defined('FORUM')) die();

function pun_stop_bots_generate_cache()
{
	global $forum_db;

	// Get the forum config from the DB
	$query = array(
		'SELECT'	=> 'id, question, answers',
		'FROM'		=> 'pun_stop_bots_questions'
	);
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$output = array();
	if ($forum_db->num_rows($result) > 0)
	{
		while ($cur_item = $forum_db->fetch_assoc($result))
			$output['questions'][$cur_item['id']] = array('question' => $cur_item['question'], 'answers' => $cur_item['answers']);
	}
	$output['cached'] = time();

	// Output config as PHP code
	$fh = @fopen(FORUM_CACHE_DIR.'cache_pun_stop_bots.php', 'wb');
	if (!$fh)
		error('Unable to write configuration cache file to cache directory. Please make sure PHP has write access to the directory \'cache\'.', __FILE__, __LINE__);

	fwrite($fh, '<?php'."\n\n".'define(\'PUN_STOP_BOTS_CACHE_LOADED\', 1);'."\n\n".'$pun_stop_bots_questions = '.var_export($output, true).';'."\n\n".'?>');

	fclose($fh);
}

function pun_stop_bots_add_question($question, $answers)
{
	global $forum_db, $pun_stop_bots_questions, $lang_pun_stop_bots;

	if (!empty($pun_stop_bots_questions['questions']) && array_search($question, array_map(create_function('$data', 'return $data[\'question\'];'), $pun_stop_bots_questions['questions'])) !== FALSE)
		return $lang_pun_stop_bots['Management err dupe question'];

	$query = array(
		'INSERT'	=>	'question, answers',
		'INTO'		=>	'pun_stop_bots_questions',
		'VALUES'	=>	'\''.$forum_db->escape($question).'\', \''.$answers.'\''
	);	
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	return true;
}

function pun_stop_bots_update_question($question_id, $question, $answers)
{
	print_r(func_get_args());
	global $forum_db, $pun_stop_bots_questions, $lang_pun_stop_bots;

	$query = array(
		'SELECT'	=>	'question, answers',
		'FROM'		=>	'pun_stop_bots_questions',
		'WHERE'		=>	'id = '.$question_id
	);
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$ids = array_keys($pun_stop_bots_questions['questions']);
	if (($cache_index = array_search($question_id, $ids)) === FALSE)
		return $lang_pun_stop_bots['Management err no question'];
	else
	{
		$old_question = $pun_stop_bots_questions['questions'][$ids[$cache_index]]['question'];
		$old_answers  = $pun_stop_bots_questions['questions'][$ids[$cache_index]]['answers'];
	}

	$update_fields = array();
	if ($old_question != $question)
		$update_fields[] = 'question = \''.$forum_db->escape($question).'\'';
	if ($old_answers != $answers)
		$update_fields[] = 'answers = \''.$forum_db->escape($answers).'\'';

	if (!empty($update_fields))
	{
		$query = array(
			'UPDATE'	=>	'pun_stop_bots_questions',
			'SET'		=>	implode(',', $update_fields),
			'WHERE'		=>	'id = '.$question_id
		);
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	}

	return true;
}

function pun_stop_bots_delete_question($question_id)
{
	global $forum_db, $pun_stop_bots_questions, $lang_pun_stop_bots;

	if (!empty($pun_stop_bots_questions['questions']) && (array_search($question_id, array_keys($pun_stop_bots_questions['questions'])) === FALSE))
		return $lang_pun_stop_bots['Management err no question'];

	$query = array(
		'DELETE'	=>	'pun_stop_bots_questions',
		'WHERE'		=>	'id = '.$question_id
	);
	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	return true;
}

?>