<?php

/**
 * pun_tags functions: tags cache, database, output
 *
 * @copyright Copyright (C) 2008 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_tags
 */

if (!defined('FORUM'))
	die();

// Generate Pun Tags cache file
function pun_tags_generate_cache()
{
	global $forum_db;

	//Fetch all topic tags
	$query = array(
		'SELECT'	=>	'tt.topic_id, tt.tag_id, tg.tag, forum_id',
		'FROM'		=>	'topic_tags AS tt',
		'JOINS'		=>	array(
			array(
				'LEFT JOIN'	=>	'topics AS t',
				'ON'		=>	'tt.topic_id = t.id'
			),
			array(
				'LEFT JOIN'	=>	'tags AS tg',
				'ON'		=>	'tt.tag_id = tg.id'
			)
		)
	);
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$pun_tags = array();
	$pun_tags['cached'] = time();
	$pun_tags['index'] = $pun_tags['topics'] = $pun_tags['forums'] = array();

	// Process all topics
	while ($cur_tag = $forum_db->fetch_assoc($result))
	{
		if (!isset($pun_tags['index'][$cur_tag['tag_id']]))
			$pun_tags['index'][$cur_tag['tag_id']] = $cur_tag['tag'];

		if (!isset($pun_tags['topics'][$cur_tag['topic_id']]))
			$pun_tags['topics'][$cur_tag['topic_id']] = array();

		$pun_tags['topics'][$cur_tag['topic_id']][] = intval($cur_tag['tag_id']);
		if (!isset($pun_tags['forums'][$cur_tag['forum_id']]))
			$pun_tags['forums'][$cur_tag['forum_id']] = array();

		if (!isset($pun_tags['forums'][$cur_tag['forum_id']][$cur_tag['tag_id']]))
			$pun_tags['forums'][$cur_tag['forum_id']][$cur_tag['tag_id']] = 1;
		else
			$pun_tags['forums'][$cur_tag['forum_id']][$cur_tag['tag_id']]++;
	}

	// Output pun tags as PHP code
	$fh = @fopen(FORUM_CACHE_DIR.'cache_pun_tags.php', 'wb');
	if (!$fh)
		error('Unable to write tags cache file to cache directory. Please make sure PHP has write access to the directory \'cache\'.', __FILE__, __LINE__);
	fwrite($fh, '<?php'."\n\n".'if (!defined(\'PUN_TAGS_LOADED\')) define(\'PUN_TAGS_LOADED\', 1);'."\n\n".'$pun_tags = '.var_export($pun_tags, true).';'."\n\n".'?>');
	fclose($fh);
}

// Generate groups permissions cache
function pun_tags_generate_forum_perms_cache()
{
	global $forum_db;

	$query = array(
		'SELECT'	=>	'id',
		'FROM'		=>	'forums'
	);
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$forums = array();

	while ($cur_forum = $forum_db->fetch_row($result))
		$forums[] = $cur_forum[0];

	if (!empty($forums))
	{
		$pun_tags_groups_perms = array();
		$pun_tags_groups_perms['cached'] = time();
		//Get all groups
		$query = array(
			'SELECT'	=>	'g_id',
			'FROM'		=>	'groups'
		);
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		while ($cur_group = $forum_db->fetch_row($result))
			$pun_tags_groups_perms[$cur_group[0]] = $forums;

		$query = array(
			'SELECT'	=>	'group_id, forum_id',
			'FROM'		=>	'forum_perms',
			'WHERE'		=>	'read_forum = 0'
		);
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		while ($cur_perm = $forum_db->fetch_assoc($result))
			unset($pun_tags_groups_perms[$cur_perm['group_id']][array_search($cur_perm['forum_id'], $forums)]);

		if (!empty($pun_tags_groups_perms))
		{
			foreach ($pun_tags_groups_perms as $group => $perms)
			{
				if ($group != 'cached')
					$pun_tags_groups_perms[$group] = array_values($perms);
			}
		}
	}

	// Output pun tags as PHP code
	$fh = @fopen(FORUM_CACHE_DIR.'cache_pun_tags_groups_perms.php', 'wb');
	if (!$fh)
		error('Unable to write tags cache file to cache directory. Please make sure PHP has write access to the directory \'cache\'.', __FILE__, __LINE__);
	fwrite($fh, '<?php'."\n\n".'if (!defined(\'PUN_TAGS_GROUPS_PERMS\')) define(\'PUN_TAGS_GROUPS_PERMS\', 1);'."\n\n".'$pun_tags_groups_perms = '.var_export($pun_tags_groups_perms, true).';'."\n\n".'?>');
	fclose($fh);
}

// Remove orphaned tags
function pun_tags_remove_orphans()
{
	global $forum_db;

	// Get orphaned tags
	$query = array(
		'SELECT'	=> 't.id, COUNT(tt.tag_id) AS cnt',
		'FROM'		=> 'tags AS t',
		'JOINS'		=> array(
			array(
				'LEFT JOIN'		=> 'topic_tags AS tt',
				'ON'			=> 't.id = tt.tag_id GROUP BY t.id'
			)
		),
		'HAVING'	=> 'COUNT(tt.tag_id) = 0'
	);
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	// Remove orphaned tags
	$ids = array();
	while ($row = $forum_db->fetch_assoc($result))
	{
		$ids[] = $row['id'];
	}

	if (!empty($ids))
	{
		$query_tags = array(
			'DELETE'	=> 'tags',
			'WHERE'		=> 'id IN ('.implode(',', $ids).')'
		);
		$forum_db->query_build($query_tags) or error(__FILE__, __LINE__);
	}
}

// Function for generating link for tags
function pun_tags_get_link($size, $tag_id, $weight, $tag)
{
	global $forum_url;
	return '<li><a href = "'.forum_link($forum_url['search_tag'], $tag_id).'"  title="'.$weight.(($weight == 1) ? (' topic') : (' topics')).'">'.$tag.'</a></li>';
}

// Get array of tags from input string
function pun_tags_parse_string($text)
{
	global $lang_pun_tags;

	if (utf8_strlen(forum_trim($text)) > 100)
		message($lang_pun_tags['Count error']);

	// Remove symbols and multiple whitespace
	$text = preg_replace('/[\'\^\$&\(\)<>`"\|@_\?%~\+\[\]{}:=\/#\\\\;!\*\.]+/', '', preg_replace('/[\s]+/', ' ', $text));
	$text = censor_words($text);
	$text = explode(',', $text);

	$results = array();
	foreach ($text as $tag)
	{
		$tmp_tag = utf8_trim($tag);
		if (!empty($tmp_tag))
			$results[] = utf8_substr_replace($tmp_tag, '', 50);
	}

	return array_unique($results);
}

// Remove topic tags
function pun_tags_remove_topic_tags($topic_id)
{
	global $forum_db;

	$query = array(
		'DELETE'	=> 'topic_tags',
		'WHERE'		=> 'topic_id = '.$topic_id
	);
	$forum_db->query_build($query) or error(__FILE__, __LINE__);
}

function pun_tags_add_new($tag, $topic_id)
{
	global $forum_db;

	$pun_tags_query = array(
		'INSERT'	=>	'tag',
		'INTO'		=>	'tags',
		'VALUES'	=>	'\''.$forum_db->escape($tag).'\''
	);
	$forum_db->query_build($pun_tags_query) or error(__FILE__, __LINE__);
	$new_tagid = $forum_db->insert_id();

	$pun_tags_query = array(
		'INSERT'	=> 'topic_id, tag_id',
		'INTO'		=> 'topic_tags',
		'VALUES'	=> $topic_id.', '.$new_tagid
	);
	$forum_db->query_build($pun_tags_query) or error(__FILE__, __LINE__);

	return $new_tagid;
}

function pun_tags_add_existing_tag($tag_id, $topic_id)
{
	global $forum_db;

	// Insert into topic_tags table
	$pun_tags_query = array(
		'INSERT'	=> 'topic_id, tag_id',
		'INTO'		=> 'topic_tags',
		'VALUES'	=> $topic_id.', '.$tag_id
	);
	$forum_db->query_build($pun_tags_query) or error(__FILE__, __LINE__);
}

function compare_tags($tag_info1, $tag_info2)
{
	return strcmp($tag_info1['tag'], $tag_info2['tag']);
}

function array_tags_slice($tags)
{
	global $forum_config;

	if (version_compare(PHP_VERSION, '5.02', '>='))
		return array_slice($tags, 0, $forum_config['o_pun_tags_count_in_cloud'], TRUE);

	$counter = count($tags) - $forum_config['o_pun_tags_count_in_cloud'];
	while ($counter > 0)
	{
		array_pop($tags);
		$counter--;
	}
	return $tags;
}

function min_max_tags_weights($tags)
{
	$max_pop = -10000000;
	foreach ($tags as $tag_id => $tag_info)
		if ($tag_info['weight'] > $max_pop)
			$max_pop = $tag_info['weight'];
	$min_pop = 10000000;
	foreach ($tags as $tag_id => $tag_info)
		if ($tag_info['weight'] < $min_pop)
			$min_pop = $tag_info['weight'];
	return array($min_pop, $max_pop);
}
function tag_cache_index($edited_tag)
{
	global $pun_tags;

	foreach ($pun_tags['index'] as $index_tag_id => $index_tag_value)
	{
		if (strcmp($index_tag_value, $edited_tag) == 0)
			return $index_tag_id;
	}
	return FALSE;
}

?>
