<?php
/***********************************************************************

	Copyright (C) 2008  PunBB

	PunBB is free software; you can redistribute it and/or modify it
	under the terms of the GNU General Public License as published
	by the Free Software Foundation; either version 2 of the License,
	or (at your option) any later version.

	PunBB is distributed in the hope that it will be useful, but
	WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston,
	MA  02111-1307  USA

***********************************************************************/

// Generate Pun Tags cache file
function pun_tags_generate_cache()
{
	global $forum_db;

	$pun_tags = array();
	$pun_tags['cached'] = time();

	// Get all tags
	$query = array(
		'SELECT'	=> 't.*, COUNT(tt.tag_id) AS cnt',
		'FROM'		=> 'tags AS t',
		'JOINS'		=> array(
			array(
				'LEFT JOIN'		=> 'topic_tags AS tt',
				'ON'			=> 't.id = tt.tag_id GROUP BY t.id'
			)
		),
		'HAVING'	=> 'cnt > 0',
		'ORDER BY'	=> 't.tag'
	);
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	if ($forum_db->num_rows($result))
	{
		while ($cur_tag = $forum_db->fetch_assoc($result))
			$pun_tags['index'][] = array('tag_id' => $cur_tag['id'], 'tag' => $cur_tag['tag'], 'weight' => $cur_tag['cnt']);

		$min_pop = $max_pop = $pun_tags['index'][0]['weight'];
		for ($i = 1; $i < count($pun_tags['index']); $i++)
		{
			if ($pun_tags['index'][$i]['weight'] < $min_pop)
				$min_pop = $pun_tags['index'][$i]['weight'];

			if ($pun_tags['index'][$i]['weight'] > $max_pop)
				$max_pop = $pun_tags['index'][$i]['weight'];
		}
		$pun_tags['min_pop'] = $min_pop;
		$pun_tags['max_pop'] = $max_pop;

		// Get tags for every tagged topic
		$query = array(
			'SELECT'	=> '*',
			'FROM'		=> 'topic_tags'
		);
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		while ($tag = $forum_db->fetch_assoc($result))
			$pun_tags['topics'][ $tag['topic_id'] ][] = $tag['tag_id'];

		// Get tags for every forum, which topics are tagged
		$query = array(
			'SELECT'	=> 	'id, forum_id',
			'FROM'		=> 	'topics',
			'WHERE'		=>	'id IN ('.implode(',', array_keys($pun_tags['topics'])).')'
		);
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		while ($cur = $forum_db->fetch_assoc($result))
			if (isset($pun_tags['forums'][$cur['forum_id']]))
			{
				for ($tag_num = 0; $tag_num < count($pun_tags['topics'][$cur['id']]); $tag_num++)
					if (!in_array($pun_tags['topics'][$cur['id']][$tag_num], $pun_tags['forums'][$cur['forum_id']]))
						$pun_tags['forums'][$cur['forum_id']][] = $pun_tags['topics'][$cur['id']][$tag_num];
			}
			else
				$pun_tags['forums'][$cur['forum_id']] = $pun_tags['topics'][ $cur['id'] ];
	}
	else
	{
		$pun_tags['min_pop'] = 0;
		$pun_tags['max_pop'] = 0;
		$pun_tags['cached'] = 0;
	}

	// Output pun tags as PHP code
	$fh = @fopen(FORUM_CACHE_DIR.'cache_pun_tags.php', 'wb');
	if (!$fh)
		error('Unable to write tags cache file to cache directory. Please make sure PHP has write access to the directory \'cache\'.', __FILE__, __LINE__);
	fwrite($fh, '<?php'."\n\n".'if (!defined(\'PUN_TAGS_LOADED\')) define(\'PUN_TAGS_LOADED\', 1);'."\n\n".'$pun_tags = '.var_export($pun_tags, true).';'."\n\n".'?>');
	fclose($fh);
}

// Remove orphaned tags
function pun_tags_remove_orphans()
{
	global $forum_db;

	// Get orphaned tags
	$query = array(
		'SELECT'	=> 't.*, COUNT(tt.tag_id) AS cnt',
		'FROM'		=> 'tags AS t',
		'JOINS'		=> array(
			array(
				'LEFT JOIN'		=> 'topic_tags AS tt',
				'ON'			=> 't.id = tt.tag_id GROUP BY t.id'
			)
		),
		'HAVING'	=> 'cnt = 0'
	);
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	// Remove orphaned tags
	while($row = $forum_db->fetch_assoc($result))
	{
		$query_tags = array(
			'DELETE'	=> 'tags',
			'WHERE'		=> 'id='.$row['id']
		);
		$forum_db->query_build($query_tags) or error(__FILE__, __LINE__);
	}
}

// Function for generating link for tags
function pun_tags_get_link($size, $tag_id, $weight, $tag)
{
	global $forum_url;
	return '<a style="font-size:'.$size.'%;" href = "'.forum_link($forum_url['search_tag'], $tag_id).'"  title="'.$weight.(($weight == 1) ? (' topic') : (' topics')).'">'.$tag.'</a>';
}

// Get array of tags from input string
function pun_tags_parse_string($text)
{
	// Remove symbols and multiple whitespace
	$text = preg_replace('/[\'\^\$&\(\)<>`"\|@_\?%~\+\[\]{}:=\/#\\\\;!\*\.]+/', '', $text);
	$text = preg_replace('/[\s]+/', ' ', $text);
	$text = array_unique(explode(',', $text));

	$results = array();
	foreach ($text as $tag)
	{
		$tmp_tag = trim($tag);
		if (!empty($tmp_tag))
			$results[] = $tmp_tag;
	}

	return $results;
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

function pun_tags_add_new( $pun_tag, $tid )
{
	global $forum_db;

	$pun_tags_query = array(
		'SELECT'	=> 'id',
		'FROM'		=> 'tags',
		'WHERE'		=> 'tag = \''.$forum_db->escape($pun_tag).'\''
	);

	$result = $forum_db->query_build($pun_tags_query) or error(__FILE__, __LINE__);

	if ($row = $forum_db->fetch_assoc($result))
		$tag_id = $row['id'];
	else
	{
		// Insert into tags table
		$pun_tags_query = array(
			'INSERT'	=> 'tag',
			'INTO'		=> 'tags',
			'VALUES'	=> '\''.$forum_db->escape($pun_tag).'\''
		);
		$forum_db->query_build($pun_tags_query) or error(__FILE__, __LINE__);
		$tag_id = $forum_db->insert_id();
	}

	// Insert into topic_tags table
	$pun_tags_query = array(
		'INSERT'	=> 'topic_id, tag_id',
		'INTO'		=> 'topic_tags',
		'VALUES'	=> $tid.', '.$tag_id
	);
	$forum_db->query_build($pun_tags_query) or error(__FILE__, __LINE__);
}

?>