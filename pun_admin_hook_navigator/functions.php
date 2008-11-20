<?

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

// Get all *.php files in $root_dir including subdirectories
function pun_admin_hook_navigator_get_files_tree($root_dir, &$tree, $subdir = '')
{
	$dir = $root_dir.$subdir;
	$dh  = opendir($dir);
	while (false !== ($filename = readdir($dh)))
	{
		$dir_files[] = $filename;
	}

	sort($dir_files);

	foreach ($dir_files as $filename)
		if (is_dir($root_dir.$subdir.$filename))
		{
			if (preg_match('/^\.\S*/', $filename) == 0)
				pun_admin_hook_navigator_get_files_tree($root_dir, $tree, $subdir.$filename.'/');  //
		}
		else
		{
			if (preg_match('/^(\S*)\.php$/', $filename, $filelist) != 0)
				$tree[] = $subdir.$filename;
		}
}

// Get included files
function pun_admin_hook_navigator_get_file_inclusions()
{
	$inclusions = array();

	$general_dir = realpath(FORUM_ROOT);

	foreach (get_included_files() as $filename)
	{	//different working with different OSes. I don't know why...
		$length_of_genera_dir = strlen($general_dir);
		$inclusions[]=substr_replace($filename,'', 0,$filename[$length_of_genera_dir]=='/'? $length_of_genera_dir+1 : $length_of_genera_dir);
	}

	return $inclusions;
}

function pun_admin_hook_navigator_get_file_hooks($root_dir = FORUM_ROOT, $filename)
{
	global $forum_db;

	$filename = str_replace('\\','/',$filename);

	$file_hooks = array();

	$query = array(
		'SELECT'	=> 'filename, hook_id',
		'FROM'		=> 'hook_navigator_file_hooks',
		'WHERE'		=> 'filename = \''.$forum_db->escape($filename).'\''
	);

	$result = $forum_db->query_build($query);

	if ($result === false || $forum_db->num_rows($result) == 0)
	{
		if ($result === false)
		{
			$schema = array(
				'FIELDS'		=> array(
					'filename'			=> array(
						'datatype'		=> 'VARCHAR(255)',
						'allow_null'	=> false
					),
					'hook_id'		=> array(
						'datatype'		=> 'VARCHAR(255)',
						'allow_null'	=> false,
					),
					'hook_position'		=> array(
						'datatype'		=> 'INT(10)',
						'allow_null'	=> false,
					),
				),
			);

			$forum_db->create_table('hook_navigator_file_hooks', $schema);
		}

		if (file_exists($root_dir.$filename))
		{
		
			$lines = file($root_dir.$filename);
			$hooks_position = array();
			foreach ($lines as $line_num => $line)	
			{
				$matches = array();
				preg_match_all('#\(\$hook = get_hook\(\'([\w\-]+)\'\)\) \? eval\(\$hook\) : null;#', $line,$matches);
				if (!empty($matches))
				{
					foreach ($matches[1] as $hook_id)
					{				
						$file_hooks[] = $hook_id;
						$hooks_position[] = $line_num + 1;
					}
				}
				else 
					$file_hooks[] = '';
			}
			$query_values = array();
			$num = 0;
			foreach ($file_hooks as $hook_id)
			{	
				$query_values[] = '\''.$forum_db->escape($filename).'\', \''.$forum_db->escape($hook_id).'\''.', \''.$hooks_position[$num].'\'';
				$num = $num + 1;
			}
			
			$query = array(
				'INSERT'	=> '`filename`,`hook_id`,`hook_position`',
				'INTO'		=> 'hook_navigator_file_hooks',
				'VALUES'	=> implode('), (', $query_values)
			);

			$forum_db->query_build($query);
		}
	}
	else
		while($cur_hook_row = $forum_db->fetch_assoc($result))
			if (!empty($cur_hook_row['hook_id']))
				$file_hooks[] = $cur_hook_row['hook_id'];

	return $file_hooks;
}

function pun_admin_hook_navigator_clear_hooks_cache()
{
	global $forum_db;
	return $forum_db->query_build(array('DELETE' => 'hook_navigator_file_hooks'));
}


function pun_admin_hook_navigator_cache_all_hooks($root_dir = FORUM_ROOT, $clear_cache_before = true)
{
	if ($clear_cache_before)
		pun_admin_hook_navigator_clear_hooks_cache();

	pun_admin_hook_navigator_get_files_tree($root_dir, $files);

	foreach ($files as $file_path)
		pun_admin_hook_navigator_get_file_hooks($root_dir, $file_path);
}

function pun_admin_hook_navigator_array_has_values($array_branch)
{
	if (is_array($array_branch))
		foreach ($array_branch as $array_item)
			if (pun_admin_hook_navigator_array_has_values($array_item))
				return true;

	return empty($item);
}

function pun_admin_hook_navigator_get_file_hook_codes($root_dir = FORUM_ROOT, $filename = '', $only_hook_id = false)
{
	global $forum_db, $forum_user, $ext_info, $lang_pun_admin_hook_navigator;

	if (file_exists($ext_info['path'].'/lang/'.$forum_user['language'].'/'.$ext_info['id'].'.php'))
		require $ext_info['path'].'/lang/'.$forum_user['language'].'/'.$ext_info['id'].'.php';
	else
		require $ext_info['path'].'/lang/English/'.$ext_info['id'].'.php';

 	$executed_hooks = pun_admin_hook_navigator_register_hook_call_end(0,0,0,true);

	$files = ($filename == '') ? pun_admin_hook_navigator_get_file_inclusions(): array($filename);

	$file_hooks = array();

	foreach ($files as $cur_file)
	{
		$hooks = pun_admin_hook_navigator_get_file_hooks($root_dir, $cur_file);

		if ($only_hook_id !== false)
		{
			if (in_array($only_hook_id, $hooks))
				$hooks = array($only_hook_id);
			else
				continue;
		}

		if (empty($hooks))
		{
			$file_hooks[$cur_file] = array();
			continue;
		}

		$file_hooks[$cur_file]['hooks_count'] = count($hooks);
		$file_hooks[$cur_file]['hooks_executed_count'] = 0;

		$query = array(
			'SELECT'	=> 'e.id as ext_id, e.title as ext_title, e.version as ext_version, e.description as ext_description, e.author as ext_author, eh.id as hook_id, eh.code as hook_code, e.disabled as ext_disabled',
			'FROM'		=> 'extension_hooks eh',
			'JOINS'		=> array(
				array(
					'JOIN'	=> 'extensions e',
					'ON'	=> 'eh.extension_id = e.id'
				)
			),
			'WHERE'		=> 'eh.id IN (\''.implode('\', \'', $hooks).'\')'
		);

		$result = $forum_db->query_build($query);

		while ($cur_hook_row = $forum_db->fetch_assoc($result))
		{
			$file_hooks[$cur_file]['content'][ $cur_hook_row['hook_id'] ]['content'][ $cur_hook_row['ext_id'] ]['info']	= array(
				$lang_pun_admin_hook_navigator['Title'] => $cur_hook_row['ext_title'],
				$lang_pun_admin_hook_navigator['Version'] => $cur_hook_row['ext_version'],
				$lang_pun_admin_hook_navigator['Description'] => $cur_hook_row['ext_description'],
				$lang_pun_admin_hook_navigator['Author'] => $cur_hook_row['ext_author'],
				$lang_pun_admin_hook_navigator['Executed'] => (isset($executed_hooks[$cur_hook_row['hook_id']][$cur_hook_row['ext_id']]))? $lang_pun_admin_hook_navigator['Yes']:$lang_pun_admin_hook_navigator['No'],
				$lang_pun_admin_hook_navigator['Disabled'] => ($cur_hook_row['ext_disabled'])? ' '.$lang_pun_admin_hook_navigator['Yes']:' '.$lang_pun_admin_hook_navigator['No']
			);

			if ($file_hooks[$cur_file]['content'][ $cur_hook_row['hook_id'] ]['content'][ $cur_hook_row['ext_id'] ]['info'][$lang_pun_admin_hook_navigator['Executed']] == $lang_pun_admin_hook_navigator['Yes'])
				 $file_hooks[$cur_file]['content'][ $cur_hook_row['hook_id'] ]['content'][ $cur_hook_row['ext_id'] ]['info'][$lang_pun_admin_hook_navigator['Extension executed in']] = $executed_hooks[$cur_hook_row['hook_id']][$cur_hook_row['ext_id']]['executed_in'];

			$file_hooks[$cur_file]['content'][ $cur_hook_row['hook_id'] ]['content'][ $cur_hook_row['ext_id'] ]['code'][] = $cur_hook_row['hook_code'];

			if (isset($executed_hooks[$cur_hook_row['hook_id']][$cur_hook_row['ext_id']]))
			 	$file_hooks[$cur_file]['hooks_executed_count']++;

			if (!isset($file_hooks[$cur_file]['content'][ $cur_hook_row['hook_id']]['code_count']))
				$file_hooks[$cur_file]['content'][ $cur_hook_row['hook_id'] ]['code_count'] = 1;
			else
				$file_hooks[$cur_file]['content'][ $cur_hook_row['hook_id'] ]['code_count']++;

			if (!isset($file_hooks[$cur_file]['code_count']))
				$file_hooks[$cur_file]['code_count'] = 1;
			else
				$file_hooks[$cur_file]['code_count']++;
		}

		foreach ($hooks as $hook_id)
			if (!isset($file_hooks[$cur_file]['content'][$hook_id]))
				$file_hooks[$cur_file]['content'][$hook_id] = array();
	}

	return $file_hooks;
}

function pun_admin_hook_navigator_drop_cache_tables()
{
	global $forum_db;
	return $forum_db->drop_table('hook_navigator_file_hooks');
	
}

function pun_admin_hook_navigator_show_hooks_tree_item($item_id, $ul_id, $hooks_count, $hooks_executed_count,$code_count, $text, $tabs, $bool_file)
{
	global $forum_db, $forum_user, $ext_info, $lang_pun_admin_hook_navigator;

	if (file_exists($ext_info['path'].'/lang/'.$forum_user['language'].'/'.$ext_info['id'].'.php'))
		require $ext_info['path'].'/lang/'.$forum_user['language'].'/'.$ext_info['id'].'.php';
	else
		require $ext_info['path'].'/lang/English/'.$ext_info['id'].'.php';

	$tabs = str_repeat("\t", $tabs);

	if (!isset($show_button))
	{
		static $show_button;
		$query = array(
			'SELECT'	=> 'conf_value',
			'FROM'		=> 'config',
			'WHERE'		=> 'conf_name = \'o_hn_list_visible\''
			);
		$result = $forum_db->query_build($query);
		$conf_row = $forum_db->fetch_assoc($result);
		$show_button = $conf_row['conf_value'];
	}

	if (!($show = (substr($item_id,0,6) == 'search')))
		$show = $show_button;

	if ($code_count > 0)
	{
		if ($bool_file == 0)
		{	
			$query = array(
				'SELECT'	=> 'hook_position',
				'FROM'		=> 'hook_navigator_file_hooks',
				'WHERE'		=> 'hook_id = \''.$forum_db->escape($text).'\''
			);
			$result = $forum_db->query_build($query);
			$result_mas = $forum_db->fetch_assoc($result);
			echo $tabs.'<li id="item_'.$item_id.'" class="has_codes"><span style="color:black">'.'#'.$result_mas['hook_position'].': '.'</splan><a href="#" onclick="return Switch(\''.$ul_id.'\')">'.htmlspecialchars($text).' (extensions: '.intval($code_count);
		}
		else 
			echo $tabs.'<li id="item_'.$item_id.'" class="has_codes"><a href="#" onclick="return Switch(\''.$ul_id.'\')">'.htmlspecialchars($text).' (extensions: '.intval($code_count);
		if ($hooks_executed_count) echo ', '.$lang_pun_admin_hook_navigator['Executed'].': '.intval($hooks_executed_count);
		echo ')</a></li>';
	}
	else if ($show)
		if ($hooks_count > 0)
		{
			echo $tabs.'<li id="item_'.$item_id.'" class="has_hooks"><a href="#" onclick="return Switch(\''.$ul_id.'\')">'.htmlspecialchars($text).'</a></li>';	
		}
		else
		{
			if ($bool_file == 0)
			{	
				$query = array(
					'SELECT'	=> 'hook_position',
					'FROM'		=> 'hook_navigator_file_hooks',
					'WHERE'		=> 'hook_id = \''.$forum_db->escape($text).'\''
				);
				$result = $forum_db->query_build($query);
				$result_mas = $forum_db->fetch_assoc($result);
				echo $tabs.'<li id="item_'.$item_id.'" onclick="Switch(\''.$ul_id.'\')">'.'#'.$result_mas['hook_position'].': '.htmlspecialchars($text).'</li>';
			}
			else 
				echo $tabs.'<li id="item_'.$item_id.'" onclick="Switch(\''.$ul_id.'\')">'.htmlspecialchars($text).'</li>';
		}
			


}

function pun_admin_hook_navigator_show_hooks_tree($files, $id_prefix = '')
{
	global $ext_info, $forum_user, $lang_pun_admin_hook_navigator;

	if (file_exists($ext_info['path'].'/lang/'.$forum_user['language'].'/'.$ext_info['id'].'.php'))
		require $ext_info['path'].'/lang/'.$forum_user['language'].'/'.$ext_info['id'].'.php';
	else
		require $ext_info['path'].'/lang/English/'.$ext_info['id'].'.php';

	if (empty($files))
		return false;

	?>
	
	<div id="<?php echo $id_prefix; ?>hook_navigator" class="databox">
	<h3 class="legend"><span><?php $lang_pun_admin_hook_navigator['Included files'] ?></span></h3>
	<ul class="files">

	<?php

	$item_id = 0;

	foreach ($files as $file_name => $file_array)
	{
		$item_id++;
		$ul_id = $id_prefix.'item_'.$item_id.'_list';

		pun_admin_hook_navigator_show_hooks_tree_item($id_prefix.$item_id, $ul_id,
			isset($file_array['hooks_count'])?$file_array['hooks_count']:0,
			isset($file_array['hooks_executed_count'])?$file_array['hooks_executed_count']:0,
			isset($file_array['code_count'])?$file_array['code_count']:0,
			$file_name, 2, 1);

		if (isset($file_array['content']))
		{

			?>

		<li style="display: none;" id="<?php echo $ul_id;?>" class="databox"><ul class="hooks"><li>
			<h3 class="legend"><span><?php echo $lang_pun_admin_hook_navigator['File hooks'] ?></span></h3></li>

			<?php

			foreach ($file_array['content'] as $hook_id => $hook_array)
			{
				$item_id++;
				$ul_id = $id_prefix.'item_'.$item_id.'_list';
				
				pun_admin_hook_navigator_show_hooks_tree_item($id_prefix.$item_id, $ul_id, 0, 0,
					isset($hook_array['code_count']) ? $hook_array['code_count'] : 0,
					$hook_id, 3, 0);

				if (isset($hook_array['content']))
				{
				
				?>
			
			<li id="<?php echo $ul_id; ?>" style="display: none;" class="databox"><ul class="extensions" >
				<li>
					<h3 class="legend"><span><?php echo $lang_pun_admin_hook_navigator['Hook extensions'] ?></span></h3></li>

				<?php

					foreach ($hook_array['content'] as $extension_id => $extension_array)
					{
						$item_id++;

						?>

				<li id="item_<?php echo $id_prefix.$item_id; ?>" class="extension_name<? if ($extension_array['info']['Executed'] == $lang_pun_admin_hook_navigator['Yes']) echo '_executed';?>"><?php echo htmlspecialchars($extension_id); ?></li>
				<li>
					<ul class="extension_info<? if ($extension_array['info']['Disabled']!=(' '.$lang_pun_admin_hook_navigator['No'])) echo '_disabled';?>">

						<?php

						foreach ($extension_array['info'] as $info_caption => $info_value)
						{

						?>

						<li><?php echo htmlspecialchars($info_caption); ?>: <?php echo htmlspecialchars($info_value); ?></li>

						<?php

						}

						echo '<li><a href="'.FORUM_ROOT.'/admin/extensions.php?section=manage&amp;flip='.$extension_id.'&amp;csrf_token='.generate_form_token('flip'.$extension_id).'">'.($extension_array['info']['Disabled'] != ' '.$lang_pun_admin_hook_navigator['Yes'] ? $lang_pun_admin_hook_navigator['Disable'] : $lang_pun_admin_hook_navigator['Enable']).'</a></li>';

						?>

					</ul>
				</li>
				<li>
					<ul class="extension_info<? if ($extension_array['info']['Executed']!=$lang_pun_admin_hook_navigator['Yes']) echo '_not_executed';?>">

					<?php

						foreach ($extension_array['code'] as $code)
						{
							$code = '<?php'."\n".$code;
							$code = highlight_string($code,true);
							$string_mas = array();
							$string_mas = split("<br />",$code);
							$number_of_string = count($string_mas);
							$rate = 1; 
							$k = 10;
							while ($number_of_string>$k)
							{
								$rate++;
								$k = $k * 10;
							}		
							$in_rate = 1;
							$m = 10; 
							for ($i = 1; $i < count($string_mas); $i++)
							{
								$string_number = '';
								if ($i > $m-1)
								{
									$in_rate++;
									$m = $m*10;
								}
								for ($j=0;$j<$rate-$in_rate;$j++)
									$string_number .= '  ';
								$string_number .= $i;	
								$string_mas[$i] = '<span class="num_line">'.$string_number.'</span>    '.$string_mas[$i];
							}
							$string_mas1 = array();
							for ($i=1;$i<count($string_mas);$i++)
								$string_mas1[$i-1] = $string_mas[$i];
							$code = implode("<br />",$string_mas1);
						?>
						
						<li><pre class="codebox"><?php echo $code; ?></pre></li>
						
						<?php

						}
					?>

					</ul></li>

					<?php

					}

				?>

				</ul></li>

				<?php

				}
			}

			?>

			</ul></li>

			<?php

		}
	}

	?>

	</ul></div>

	<?php

	return true;
}

function pun_admin_hook_navigator_show_cur_page_hooks_tree()
{
	// TODO: Get the correct filename value

	if (preg_match('#((?:admin/)?[^\s/]+.php)$#', $_SERVER['SCRIPT_FILENAME'], $matches) == 0)
		return false;

	return pun_admin_hook_navigator_show_hooks_tree(pun_admin_hook_navigator_get_file_hook_codes());
}

function pun_admin_hook_navigator_get_files_by_hook_id($hook_id, &$files)
{
	global $forum_db;

	$query = array(
		'SELECT'	=> 'filename',
		'FROM'		=> 'hook_navigator_file_hooks',
		'WHERE'		=> 'hook_id = \''.$forum_db->escape($hook_id).'\''
	);

	$result = $forum_db->query_build($query);

	if ($forum_db->num_rows($result) == 0)
		return false;

	while($cur_filename_row = $forum_db->fetch_assoc($result))
		$files[] = $cur_filename_row['filename'];

	return count($files);
}

function pun_admin_hook_navigator_show_hook_files($hook_id)
{
	global  $forum_user, $ext_info, $lang_pun_admin_hook_navigator;

	if (file_exists($ext_info['path'].'/lang/'.$forum_user['language'].'/'.$ext_info['id'].'.php'))
		require $ext_info['path'].'/lang/'.$forum_user['language'].'/'.$ext_info['id'].'.php';
	else
		require $ext_info['path'].'/lang/English/'.$ext_info['id'].'.php';

	$files = array();

	if (pun_admin_hook_navigator_get_files_by_hook_id($hook_id, $files))
	{

		?>

		<div class="main-content frm">
			<div class="frm-head">
				<h2><span><?php echo $lang_pun_admin_hook_navigator['Hook search results'] ?></span></h2>
			</div>
			<div class="frm-info" id="hook-navigator-search-result">
				<p><strong><?php echo count($files); ?></strong> <?php echo $lang_pun_admin_hook_navigator['Files calling hook'] ?> <strong class="hook_id"><?php echo htmlspecialchars($hook_id); ?></strong> <?php $lang_pun_admin_hook_navigator['Found'].'.' ?></p>
			</div>

		<?php

		foreach ($files as $filename)
		{
			$file_hooks = pun_admin_hook_navigator_get_file_hook_codes(FORUM_ROOT, $filename, $hook_id);

			pun_admin_hook_navigator_show_hooks_tree($file_hooks, 'search_');
		}

		?>

		</div>

		<?php

	}
	else
	{

	?>

			<div class="frm-info" id="hook-navigator-search-result">
				<p><?php echo $lang_pun_admin_hook_navigator['Hook']?> <span class="hook_id"><?php echo htmlspecialchars($hook_id); ?></span> <?php echo $lang_pun_admin_hook_navigator['Not found'].'.' ?></p>
			</div>

	<?

	}
}


function pun_admin_hook_navigator_show_hook_navigator_section()
{

	global $ext_info, $forum_url, $forum_page, $forum_db, $forum_user, $lang_pun_admin_hook_navigator;

	if (file_exists($ext_info['path'].'/lang/'.$forum_user['language'].'/'.$ext_info['id'].'.php'))
		require $ext_info['path'].'/lang/'.$forum_user['language'].'/'.$ext_info['id'].'.php';
	else
		require $ext_info['path'].'/lang/English/'.$ext_info['id'].'.php';

	if(isset($_POST['bt_list_visible']))
	{
		if(isset($_POST['checkbox_list_visible']))
			$query = array(
				'UPDATE'	=> 'config',
				'SET'		=> 'conf_value=\'1\'',
				'WHERE'		=> 'conf_name=\'o_hn_list_visible\''
			);
		else
			$query = array(
				'UPDATE'	=> 'config',
				'SET'		=> 'conf_value=\'0\'',
				'WHERE'		=> 'conf_name=\'o_hn_list_visible\''
			);

		$forum_db->query_build($query) or error(__FILE__, __LINE__);
	}

	if (isset($_POST['hook_search_form_sent']) && $_POST['hook_search_form_sent'] == 1 &&isset($_POST['hook_id']))
	{
		$hook_search_form_sent = true;
		$hook_id = preg_replace('#[^\w\-]#', '', $_POST['hook_id']);
		$update_cache = isset($_POST['update_cache']) && $_POST['update_cache'] == 1;
	}
	else
	{
		$hook_search_form_sent = false;
		$hook_id = '';
		$update_cache = false;
	}

	if ($update_cache)
	{
		pun_admin_hook_navigator_cache_all_hooks(FORUM_ROOT, true);
	}

	?>

	<div id="pun-main" class="main sectioned admin">
		<?php echo generate_admin_menu(); ?>
		<div class="main-head">
			<h1><span>{ <?php echo end($forum_page['crumbs']) ?> }</span></h1>
		</div>

	<?php

	if ($hook_search_form_sent && strlen($hook_id) > 3)
		pun_admin_hook_navigator_show_hook_files($hook_id);

	$query = array(
		'SELECT'	=> 'conf_value',
		'FROM'		=> 'config',
		'WHERE'		=> 'conf_name = \'o_hn_list_visible\''
	);

	$result = $forum_db->query_build($query);
	$conf_row = $forum_db->fetch_assoc($result);

	$checked = $conf_row['conf_value'];

	?>

		<div class="main-content frm">
			<div class="frm-head">
				<h2><span><?php echo $lang_pun_admin_hook_navigator['Search hook by id'] ?></span></h2>
			</div>
			<form action="<?php echo forum_link($forum_url['admin_extensions_hook_search']) ?>" method="post" class="frm-form">
				<div class="frm-info">
					<p><?php echo $lang_pun_admin_hook_navigator['Enter hook ID'] ?></p>
					<p class="warn"><strong><?php echo $lang_pun_admin_hook_navigator['WARNING'] ?></strong> <?php echo $lang_pun_admin_hook_navigator['Update hook search'] ?></p>
				</div>
				<fieldset class="frm-set set1">
					<div class="frm-fld text">
						<label for="fld_hook_id">
							<span class="fld-label"><?php echo $lang_pun_admin_hook_navigator['Hook ID'] ?></span>
							<span class="fld-input"><input type="text" id="fld_hook_id" name="hook_id" value="<?php echo $hook_id ?>" /></span>
						</label>
					</div>
					<div class="radbox checkbox">
						<label for="fld_update_cache">
							<span class="fld-label"><?php echo $lang_pun_admin_hook_navigator['Update cache'] ?></span>
							<span class="fld-input"><input type="checkbox" id="fld_update_cache" name="update_cache" value="1" <?php echo $update_cache ? 'checked="checked"' : ''; ?> /> <?php echo $lang_pun_admin_hook_navigator['Clear cache and refresh hook locations'] ?></span>
						</label>
					</div>
					<div class="hidden">
						<input type="hidden" name="section" value="hook_search" />
						<input type="hidden" name="hook_list_file_sent" value="1" />
						<input type="hidden" name="hook_search_form_sent" value="1" />
						<input type="hidden" name="csrf_token" value="<?php echo generate_form_token(forum_link($forum_url['admin_extensions_hook_search'])); ?>" />
					</div>
				</fieldset>
					<div class="frm-buttons">
						<span class="submit">
							<input type="submit" value=<?php echo $lang_pun_admin_hook_navigator['Submit search'] ?> />
						</span>
					</div>
			</form>
		</div>
		<div class="main-content frm">
			<div class="frm-head">
				<h2><span><?php echo $lang_pun_admin_hook_navigator['Settings'] ?></span></h2>
			</div>
			<form action="<?php echo forum_link($forum_url['admin_extensions_hook_search']) ?>" method="post" class="frm-form">
				<div class="radbox checkbox">
					<span class="fld-label"><?php echo $lang_pun_admin_hook_navigator['List files having no code'] ?></span>
						<input type="checkbox" name="checkbox_list_visible" <? if($checked) echo 'checked="checked"';?>/><?php echo $lang_pun_admin_hook_navigator['Show included files'] ?>
				</div>
				<div class="frm-buttons">
					<span class="submit"><input type="submit" name="bt_list_visible" value=<?php echo $lang_pun_admin_hook_navigator['Apply'] ?> />
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token(forum_link($forum_url['admin_extensions_hook_search'])); ?>" />
			<input type="hidden" name="section" value="hook_search" />
					</span>
				</div>
			</form>
		</div>
	</div>

	<?php
}


function pun_admin_hook_navigator_add_register_function()
{
	global $forum_hooks;

	$id = 0;
	$all_hook_id = array_keys($forum_hooks);

	foreach ($all_hook_id as $hook_id)
	{
		if ($n = count($forum_hooks[$hook_id]))
			while($n--)
			{
				$id++;

				if (preg_match('~\'id\'\s+=>\s+\'(\w+)\'~', $forum_hooks[$hook_id][$n], $matches))
					$forum_hooks[$hook_id][$n] = "\n".
						'pun_admin_hook_navigator_register_hook_call_start(\''.$id.'\');'."\n".
							$forum_hooks[$hook_id][$n]. "\n".
						'pun_admin_hook_navigator_register_hook_call_end(\''.$id.'\',\''.$hook_id.'\',\''.$matches[1].'\');'."\n";
			}
	}
}

function pun_admin_hook_navigator_register_hook_call_start($id, $return = false)
{
	static $time_hook_id = array();

	if ($return) return $time_hook_id[$id];

	$time_hook_id[$id] = microtime(true);
}

function pun_admin_hook_navigator_register_hook_call_end($id, $hook_id, $extension_id, $return = false)
{
	static $called_hooks = array();

	if ($return)
		return $called_hooks;

	$called_hooks[$hook_id][$extension_id] = array('id'=>$id, 'executed_in' => number_format((microtime(true)-pun_admin_hook_navigator_register_hook_call_start($id,true))*1000,3) .' ms.');
}

?>
