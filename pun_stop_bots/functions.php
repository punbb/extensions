<?php

/**
 * pun_stop_bots functions file
 *
 * @copyright (C) 2008-2011 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_stop_bots
 */

if (!defined('FORUM')) die();

define('PUN_STOP_BOTS_COOKIE_NAME', 'pun_stop_bots_cookie');

function pun_stop_bots_generate_cache()
{
	global $forum_db;

	// Get the forum config from the DB
	$query = array(
		'SELECT'	=> 'id, question, answers',
		'FROM'		=> 'pun_stop_bots_questions'
	);
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$output = $questions = array();
	while ($row = $forum_db->fetch_assoc($result))
	{
		$questions[] = $row;
	}

	if (!empty($questions))
	{
		foreach ($questions as $cur_item)
		{
			$output['questions'][$cur_item['id']] = array('question' => $cur_item['question'], 'answers' => $cur_item['answers']);
		}
	}
	$output['cached'] = time();

	if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
		require FORUM_ROOT.'include/cache.php';

	if (!write_cache_file(FORUM_CACHE_DIR.'cache_pun_stop_bots.php', '<?php'."\n\n".'define(\'PUN_STOP_BOTS_CACHE_LOADED\', 1);'."\n\n".'$pun_stop_bots_questions = '.var_export($output, true).';'."\n\n".'?>'))
	{
		error('Unable to write cache_pun_stop_bots cache file to cache directory.<br/>Please make sure PHP has write access to the directory \'cache\'.', __FILE__, __LINE__);
	}
}


function pun_stop_bots_add_question($question, $answers)
{
	global $forum_db, $pun_stop_bots_questions, $lang_pun_stop_bots;

	if (!empty($pun_stop_bots_questions['questions']) && array_search($question, array_map(create_function('$data', 'return $data[\'question\'];'), $pun_stop_bots_questions['questions'])) !== FALSE)
		return $lang_pun_stop_bots['Management err dupe question'];

	$query = array(
		'INSERT'	=>	'question, answers',
		'INTO'		=>	'pun_stop_bots_questions',
		'VALUES'	=>	'\''.$forum_db->escape($question).'\', \''.$forum_db->escape($answers).'\''
	);
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	return true;
}


function pun_stop_bots_update_question($question_id, $question, $answers)
{
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


function pun_stop_bots_compare_answers($answer, $question_id)
{
	global $forum_db, $forum_user, $pun_stop_bots_questions, $lang_pun_stop_bots;

	return in_array($answer, explode(',', $pun_stop_bots_questions['questions'][$question_id]['answers']));
}


function pun_stop_bots_set_cookie($question_id)
{
	global $forum_user, $cookie_name, $cookie_path, $cookie_domain, $cookie_secure;

	$now = time();
	$expire_time = $now + 1209600;
	$expire_hash = sha1($forum_user['salt'].forum_hash($expire_time, $forum_user['salt']));
	$question_hash = forum_hash($question_id, $forum_user['salt']);

	forum_setcookie(PUN_STOP_BOTS_COOKIE_NAME, base64_encode($forum_user['id'].'|'.$question_hash.'|'.$expire_time.'|'.$expire_hash), $expire_time);
}


function pun_stop_bots_check_cookie()
{
	global $forum_user, $forum_db;

	$query = array(
		'SELECT'	=>	'pun_stop_bots_question_id',
		'FROM'		=>	'users',
		'WHERE'		=>	'id = '.$forum_user['id']
	);
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$row = $forum_db->fetch_assoc($result);

	if ($row)
	{
		$question_id = $row['pun_stop_bots_question_id'];
		$pun_stop_bots_cookie = explode('|', base64_decode($_COOKIE[PUN_STOP_BOTS_COOKIE_NAME]));
		if (count($pun_stop_bots_cookie) != 4)
		{
			return FALSE;
		}
		else
		{
			list($user_id, $question_hash, $expire_time, $expire_hash) = $pun_stop_bots_cookie;
			if ($forum_user['id'] == $user_id && forum_hash($question_id, $forum_user['salt']) == $question_hash && sha1($forum_user['salt'].forum_hash($expire_time, $forum_user['salt'])) == $expire_hash)
				return TRUE;
			else
				return FALSE;
		}
	}
	else
	{
		return FALSE;
	}
}


function pun_stop_bots_generate_guest_question_id()
{
	global $forum_db, $forum_user, $pun_stop_bots_questions;

	$question_ids = array_keys($pun_stop_bots_questions['questions']);
	$new_question_id = $question_ids[array_rand($question_ids)];
	unset($question_ids);

	$pun_stop_bots_query = array(
		'UPDATE'	=>	'online',
		'SET'		=>	'pun_stop_bots_question_id = '.$new_question_id,
		'WHERE'		=>	'ident = \''.$forum_user['ident'].'\''
	);
	$forum_db->query_build($pun_stop_bots_query) or error(__FILE__, __LINE__);

	return $new_question_id;
}


function pun_stop_bots_generate_user_question_id()
{
	global $forum_db, $forum_user, $pun_stop_bots_questions;

	$question_ids = array_keys($pun_stop_bots_questions['questions']);
	$new_question_id = $question_ids[array_rand($question_ids)];
	unset($question_ids);

	$pun_stop_bots_query = array(
		'UPDATE'	=>	'users',
		'SET'		=>	'pun_stop_bots_question_id = '.$new_question_id,
		'WHERE'		=>	'id = '.$forum_user['id']
	);
	$forum_db->query_build($pun_stop_bots_query) or error(__FILE__, __LINE__);

	return $new_question_id;
}


function pun_stop_bots_prepare_answers($answers)
{
	return preg_replace('~,[\s]+~', ',', $answers);
}

?>