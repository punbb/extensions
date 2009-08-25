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
	if ($forum_db->num_rows($result) > 0)
	{
		$pun_tags['index'] = $pun_tags['topics'] = $pun_tags['forums'] = array();
		//Process all topics
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
	if ($forum_db->num_rows($result) > 0)
	{
		while ($cur_forum = $forum_db->fetch_row($result))
			$forums[] = $cur_forum[0];
	}
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
		if ($forum_db->num_rows($result) > 0)
		{
			while ($cur_perm = $forum_db->fetch_assoc($result))
				unset($pun_tags_groups_perms[$cur_perm['group_id']][array_search($cur_perm['forum_id'], $forums)]);
			foreach ($pun_tags_groups_perms as $group => $perms)
				if ($group != 'cached')
					$pun_tags_groups_perms[$group] = array_values($perms);
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
	while ($row = $forum_db->fetch_assoc($result))
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

function pun_tags_add_new_tagid($tag_id, $topic_id)
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

function show_section_pun_tags()
{
	global $forum_user, $forum_db, $forum_page, $lang_pun_tags, $base_url, $pun_tags_url, $forum_config, $lang_topic,
		$forum_url, $lang_ul;
	
	pun_tags_generate_cache();
	include FORUM_ROOT.'cache/cache_pun_tags.php';
	
	if ((isset($pun_tags['topics'])) && (!empty($pun_tags['topics'])))
	{
		$tmp_arr = array();
		$tags_arr_topic_names = array();
		$tags_arr_ID = array();
		$tags_arr_unindex = array();
		$tags_lines_arr = array();
		$tags_forums_ID = array(); //forums ID. access by [topic_id] => forum_id
		
		foreach($pun_tags['index'] as $key => $value)
			$tags_arr_unindex[$value] = $key;
		
		$tmp_arr = $pun_tags['topics'];
		$tags_arr_ID = $tmp_arr = array_keys($tmp_arr); //topics ID
		sort($tags_arr_ID);
		$count_tags_arr_ID = count($tags_arr_ID);
		if (!empty($tmp_arr))
		{
			$query_get_names = array(
				'SELECT'	=>	'id, subject',
				'FROM'		=>	'topics',
				'WHERE'		=>	'id IN ('.implode(', ', $tmp_arr).')'
			);
			
			$result_get_names = $forum_db->query_build($query_get_names) or error(__FILE__, __LINE__);
			
			while(list($key, $value) = $forum_db->fetch_row($result_get_names))
			{
				$tags_arr_topic_names[$key] = $value;
				$tmp_arr = array();
				$tmp_arr = $pun_tags['topics'][$key];
				$count_tmp_arr = count($tmp_arr);
				$tags_lines_arr[$key] = $pun_tags['index'][$tmp_arr[0]];
				
				for ($iter = 1; $iter < $count_tmp_arr; $iter++)
				{
					$tags_lines_arr[$key] .= ', '.$pun_tags['index'][$tmp_arr[$iter]];
				}
			}
			$query_get_forums = array(
				'SELECT'	=>	't.forum_id, t.id',
				'FROM'		=>	'forums AS f',
				'JOINS'		=>	array(
					array(
						'INNER JOIN'	=>	'topics AS t',
						'ON'			=>	't.forum_id=f.id'
					),
					array(
						'INNER JOIN'	=>	'topic_tags AS tt',
						'ON'			=>	'tt.topic_id=t.id'
					)
				)
			);
			
			$result_get_forums = $forum_db->query_build($query_get_forums) or error(__FILE__, __LINE__);
			
			while(list($forum_id, $topic_id) = $forum_db->fetch_row($result_get_forums))
				$tags_forums_ID[$topic_id] = $forum_id;
		}
		if (isset($_POST['change_tags']))
		{
			if ($count_tags_arr_ID > 0)
			{
				$texts = array();
				$texts = $_POST['line_tags'];
				
				for($i = 0; $i < $count_tags_arr_ID; $i++)
				{
					$tags_arr_new = pun_tags_parse_string(trim($texts[$tags_arr_ID[$i]]));
					$tags_arr_old = explode(', ', $tags_lines_arr[$tags_arr_ID[$i]]);
					
					$intersect_arrs = array();
					$intersect_arrs = array_intersect($tags_arr_old, $tags_arr_new);
					$old_diff = array_diff($tags_arr_old, $intersect_arrs);//elements, which is for delete
					$new_diff = array_diff($tags_arr_new, $intersect_arrs);//elements, which is for create
					$cur_forum_ID = $tags_forums_ID[$tags_arr_ID[$i]]; //we find forum ID via the topic ID
					
					if (!empty($old_diff)) //elements, which is for delete
					{
						foreach($old_diff as $key => $name_tag) //explode array of difference as a key and value.
						{
							//key - position
							//value - name of tag
							$cur_tag_ID = $tags_arr_unindex[$name_tag]; //we find tag ID via the name of tag
							$cur_forum_ID = $tags_forums_ID[$tags_arr_ID[$i]]; //we find forum ID via the topic ID
							$count_tags_forum = 0;
							
							if ($pun_tags['forums'][$cur_forum_ID][$cur_tag_ID] == 1)
							{
								foreach ($pun_tags['forums'] as $forum_id => $forums_tags)
									if (array_key_exists($cur_tag_ID, $forums_tags))
										$count_tags_forum++;
								
								if ($count_tags_forum == 1)
								{
									$query_del_tags = array(
										'DELETE'	=>	'tags',
										'WHERE'		=>	'id='.$cur_tag_ID
									);
									
									$forum_db->query_build($query_del_tags) or error(__FILE__, __LINE__);
									unset($pun_tags['index'][$cur_tag_ID]);
								}
								
								unset($pun_tags['forums'][$cur_forum_ID][$cur_tag_ID]);
								
								$query_del_tags = array(
									'DELETE'	=>	'topic_tags',
									'WHERE'		=>	'(tag_id='.$cur_tag_ID.' AND topic_id='.$tags_arr_ID[$i].')'
								);
								
								$forum_db->query_build($query_del_tags) or error(__FILE__, __LINE__);
								
								$key_arr = array_search($cur_tag_ID, $pun_tags['topics'][$tags_arr_ID[$i]]);
								if ($key_arr)
									array_splice($pun_tags['topics'][$tags_arr_ID[$i]], $key_arr, 1);
							}
							else if ($pun_tags['forums'][$cur_forum_ID][$cur_tag_ID] > 1)
							{
								$query_del_tags = array(
									'DELETE'	=>	'topic_tags',
									'WHERE'		=>	'(tag_id='.$cur_tag_ID.' AND topic_id='.$tags_arr_ID[$i].')'
								);
								
								$forum_db->query_build($query_del_tags) or error(__FILE__, __LINE__);
								
								$pun_tags['forums'][$cur_forum_ID][$cur_tag_ID]--;
								$key_arr = array_search($cur_tag_ID, $pun_tags['topics'][$tags_arr_ID[$i]]);
								
								if ($key_arr)
									array_splice($pun_tags['topics'][$tags_arr_ID[$i]], $key_arr, 1);
							}
						}
					}
					$tags_arr_unindex = array();
					
					foreach($pun_tags['index'] as $key => $value)
						$tags_arr_unindex[$value] = $key;
					
					if (!empty($new_diff)) //elements, which is for create
					{
						foreach($new_diff as $key => $name_tag)
						{
							if (array_key_exists($name_tag, $tags_arr_unindex))
							{
								$cur_tag_ID = $tags_arr_unindex[$name_tag]; //we find tag ID via the name of tag
								
								pun_tags_add_existing_tag($cur_tag_ID, $tags_arr_ID[$i]);
								//update array $pun_tags
								if (array_key_exists($cur_tag_ID, $pun_tags['forums'][$cur_forum_ID]))
									$pun_tags['forums'][$cur_forum_ID][$cur_tag_ID]++;
								else
									$pun_tags['forums'][$cur_forum_ID][$cur_tag_ID] = 1;
								
								array_push($pun_tags['topics'][$tags_arr_ID[$i]], $cur_tag_ID);
							}
							else
							{
								$tag_id = pun_tags_add_new($name_tag, $tags_arr_ID[$i]);
								$pun_tags['forums'][$cur_forum_ID][$tag_id] = 1;
								array_push($pun_tags['topics'][$tags_arr_ID[$i]], $tag_id);
								$pun_tags['index'][$tag_id] = $name_tag;
							}
						}
					}
				}
				pun_tags_remove_orphans();
				pun_tags_generate_cache();
			}
			redirect(forum_link($pun_tags_url['Section pun_tags']), $lang_pun_tags['Redirect with changes']);
		}
		
		$forum_page['form_action'] = $base_url.'/'.$pun_tags_url['Section tags'];
		$forum_page['item_count'] = 0;
		
		$forum_page['table_header'] = array();
		$forum_page['table_header']['name'] = '<th class="tc'.count($forum_page['table_header']).'" scope=col">'.$lang_pun_tags['Name topic'].'</th>';
		$forum_page['table_header']['tags'] = '<th class="tc'.count($forum_page['table_header']).'" scope=col" style="width: 60%">'.$lang_pun_tags['Tags of topic'].'</th>';

		?>
		<div class="main-subhead">
			<h2 class="hn">
				<span><?php echo $lang_pun_tags['Section tags']; ?></span>
			</h2>
		</div>
		<div class="main-content main-forum">
			<form class="frm-form" id="afocus" method="post" accept-charset="utf-8" action="<?php echo $forum_page['form_action'] ?>">
				<div class="hidden">
					<input type="hidden" name="form_sent" value="1" />
					<input type="hidden" name="csrf_token" value="<?php echo generate_form_token($forum_page['form_action']) ?>" />
				</div>
					<div class="ct-group">
						<table cellspacing="0" summary="<?php echo $lang_ul['Table summary'] ?>">
							<thead>
								<tr>
									<?php echo implode("\n\t\t\t\t\t\t", $forum_page['table_header'])."\n" ?>
								</tr>
							</thead>
							<tbody>
					<?php
					
						for($iter = 0; $iter < $count_tags_arr_ID; $iter++)
						{
							$forum_page['table_row']['name'] = '<td class="tc'.count($forum_page['table_header']).'" scope=col"><a class="permalink" rel="bookmark" href="'.forum_link($forum_url['topic'], $tags_arr_ID[$iter]).'">'.forum_htmlencode($tags_arr_topic_names[$tags_arr_ID[$iter]]).'</a></td>';
							$forum_page['table_row']['tags'] = '<td class="tc'.count($forum_page['table_header']).'" scope=col"><input id="fld'.$forum_page['item_count'].'" type="text" value="'.forum_htmlencode($tags_lines_arr[$tags_arr_ID[$iter]]).'" size="100%" name="line_tags['.$tags_arr_ID[$iter].']"/></td>';
							
							++$forum_page['item_count'];
							
					?>
									<tr class="<?php echo ($forum_page['item_count'] % 2 != 0) ? 'odd' : 'even' ?><?php echo ($forum_page['item_count'] == 1) ? ' row1' : '' ?>">
										<?php echo implode("\n\t\t\t\t\t\t", $forum_page['table_row'])."\n" ?>
									</tr>
					<?php
					
						}
						
					?>
							</tbody>
						</table>
					</div>
					<div class="frm-buttons">
						<span class="submit"><input type="submit" name="change_tags" value="<?php echo $lang_pun_tags['Submit changes'] ?>" /></span>
					</div>
			</form>
		</div>
		<?php
	}
	else
	{
		?>
			<div class="main-subhead">
				<h2 class="hn">
					<span><?php echo $lang_pun_tags['Section tags']; ?></span>
				</h2>
			</div>
			<div class="main-content main-forum">
				<div class="ct-box">
					<h3 class="hn"><span><strong><?php echo $lang_pun_tags['No tags']; ?></strong></span></h3>
				</div>
			</div>
			
		<?php
	}
}
?>