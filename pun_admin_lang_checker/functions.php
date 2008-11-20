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

function pun_lang_main_function($mas_lang)
{
	$root_dir = FORUM_ROOT;
	$subdir = '';
	$tree_calls = array();
	$tree_entries = array();
	
	$mas_files_exceptions = array('lang/English/url_replace.php');
	pun_lang_check_get_files_tree_calls($root_dir, &$tree_calls,$subdir);
	pun_lang_check_get_files_tree_entries($root_dir, &$tree_entries,$subdir);
	
	   
	$lang_info = array();
	$mas_lang = array();
	
	pun_lang_checker_create_lang_info_entries($tree_entries, &$lang_info, $mas_lang);
	pun_lang_checker_create_lang_info_calls($tree_calls, &$lang_info, $mas_lang);
	pun_lang_checker_create_lang_info_diff($mas_lang, &$lang_info);
	
	return $lang_info;	
}

function pun_lang_check_get_files_tree_calls($root_dir, &$tree_calls, $subdir)
{
	$dir_files = scandir($root_dir.$subdir);
	foreach ($dir_files as $filename)
	{  
		if (is_dir($root_dir.$subdir.$filename)and($filename != 'lang'))			
			{
			if (preg_match('/^\.\S*/', $filename) == 0)
				pun_lang_check_get_files_tree_calls($root_dir, $tree_calls, $subdir.$filename.'/');
			}   
		else
		{
			if (preg_match('/^(\S*)(\.php)|(\.xml)$/',$filename,$filelist) != 0)
				$tree_calls[] = $subdir.$filename;
		}
	}	
}

function pun_lang_check_get_files_tree_entries($root_dir, &$tree_entries, $subdir)
{
	$dir_files = scandir($root_dir.$subdir);
	foreach ($dir_files as $filename)
	{
		if (is_dir($root_dir.$subdir.$filename)and($filename == 'lang'))
		{	   
			
			$dir_files_lang = scandir($root_dir.$subdir.'lang/');

			foreach ($dir_files_lang as $filename_lang)
			if (is_dir($root_dir.$subdir.'lang/'.$filename_lang)and($filename_lang == 'English'))
			{
					$dir_files_eng = scandir($root_dir.$subdir.'lang/'.'English/');
					foreach ($dir_files_eng as $filename_eng)
						if (preg_match('/^(\S*)\.php$/',$filename_eng,$filelist) != 0)
							$tree_entries[] = $subdir.'lang/'.'English/'.$filename_eng;	
			}
		}
	else
		if(is_dir($root_dir.$subdir.$filename))
		{
			if (preg_match('/^\.\S*/', $filename) == 0)
			pun_lang_check_get_files_tree_entries($root_dir, $tree_entries, $subdir.$filename.'/');
		} 
	}
}

function pun_lang_checker_create_lang_info_calls($tree_calls, &$lang_info, &$mas_lang)
{	
	$mas_files_exceptions = array('lang_url_replace');
	foreach ($tree_calls as $filename)
	{
		$str = file_get_contents(FORUM_ROOT.$filename) ;
		preg_match_all ( '/\$(lang_[A-Za-z_]+)\[\'([!-&(-} ]+)\'\]/' ,  $str, $input); 
		for ($i=0; $i< count($input[0]); $i++)
		{
			if (!array_key_exists($input[1][$i],$mas_files_exceptions))
			{
				if (in_array($input[1][$i],$mas_lang))
				{
					if (!in_array($input[2][$i],$lang_info[$input[1][$i]]['calls'])) 
						array_push($lang_info[$input[1][$i]]['calls'],$input[2][$i]);
				}
					
				else	
				{
					$lang_info[$input[1][$i]]['calls'] = array($input[2][$i]);
					$lang_info[$input[1][$i]]['entries'] = array();
					array_push($mas_lang,$input[1][$i]);
				}
			}
		}		
	}
}

function pun_lang_checker_create_lang_info_entries($tree_entries, &$lang_info, &$mas_lang)  
{
	$mas_files_exceptions = array('lang_url_replace');
	foreach ($tree_entries as $filename)
	{		
		$str = file_get_contents(FORUM_ROOT.$filename) ;
		preg_match_all ( '/\$(\S+)/' ,  $str, $input);
		if (count($input[0]>0))
		{
			$name_lang = $input[1][0];
			if (!array_key_exists($name_lang,$mas_files_exceptions))
			{
				array_push($mas_lang,$name_lang); 
				$lang_info[$name_lang]['entries'] = array();
				$lang_info[$name_lang]['calls'] = array();		
				preg_match_all ('/\'(.+)\'(\040|\011)*=/' ,  $str, $input);	
				for ($i=0; $i< count($input[0]); $i++)
				{	
					if (!in_array($input[1][$i],$lang_info[$name_lang]['entries'])) 
							array_push($lang_info[$name_lang]['entries'],$input[1][$i]);
				}
			}
		}
	}
}

function pun_lang_checker_create_lang_info_diff($mas_lang,&$lang_info)
{
	foreach ($mas_lang as $name_lang)
	{
		$lang_info[$name_lang]['obsolete_calls'] = array_diff($lang_info[$name_lang]['calls'], $lang_info[$name_lang]['entries']);
		$lang_info[$name_lang]['obsolete_entries'] = array_diff($lang_info[$name_lang]['entries'], $lang_info[$name_lang]['calls']);
	}
}

function pun_lang_ckecker_create_result_file($lang_info, $mas_lang, $language)
{	
	global $ext_info;
	if($file = fopen($ext_info['path'].'/result.doc','w'))
	{
		global $forum_user, $lang_pun_admin_lang_checker;

		fwrite($file,$lang_pun_admin_lang_checker['Obsolete_calls']."\n");
		foreach ($mas_lang as $name_lang)
		{
			$mas = $lang_info[$name_lang]['obsolete_calls'];
			if (count($mas)>0) 
				fwrite($file,"\n\t".$name_lang.':'."\n");	
			foreach ($mas as $element)
			{
				fwrite($file,"\t\t".$element."\n");
			}
		}		
		fwrite($file,"\n\n".$lang_pun_admin_lang_checker['Obsolete_entries']."\n");
		foreach ($mas_lang as $name_lang)
		{
			$mas = $lang_info[$name_lang]['obsolete_entries'];
			if (count($mas)>0)		
				fwrite($file,"\n\t".$name_lang.':'."\n");
			foreach ($mas as $element)
			{
				fwrite($file,"\t\t".$element."\n");
			}
		}
		return 1;
	}	
	else return 0;	
}

function pun_lang_checker_do_texts($lang_info, $mas_lang, &$text_calls, &$text_entries)
{
	$text_calls = '';
	$text_entries = '';
	
	foreach ($mas_lang as $name_lang)
	{
		$mas = $lang_info[$name_lang]['obsolete_calls'];
		if (count($mas)>0) 
			$text_calls .= "\n\t".$name_lang.':'."\n";	
		foreach ($mas as $element)
		{
			$text_calls .= "\t\t".$element."\n";
		}
	}		
	
	foreach ($mas_lang as $name_lang)
	{
		$mas = $lang_info[$name_lang]['obsolete_entries'];
		if (count($mas)>0)		
			$text_entries .= "\n\t".$name_lang.':'."\n";
		foreach ($mas as $element)
		{
			$text_entries .= "\t\t".$element."\n";
		}
	}
	pun_lang_checker_do_cache($text_calls, $text_entries);
}

function pun_lang_checker_show_results($text_calls, $text_entries)
{
		global $ext_info, $forum_page, $lang_pun_admin_lang_checker;
		
		$text_calls = $lang_pun_admin_lang_checker['Obsolete_calls']."\n".$text_calls;
		$text_entries = $lang_pun_admin_lang_checker['Obsolete_entries']."\n".$text_entries;
		?>
		<div id="pun-main" class="main sectioned admin">
			<?php echo generate_admin_menu(); ?>
			<div class="main-head">
				<h1><span>{ <?php echo end($forum_page['crumbs']) ?> }</span></h1>
			</div>
			
			<div class="main-content frm">
				<div class="frm-head">
					<h2><span><?php echo $lang_pun_admin_lang_checker['Show results'] ?></span></h2>
				</div>
				<form class="frm-form">
					<label for="fld<?php echo ++$forum_page['fld_count'] ?>">
							<span class="fld-input"><textarea id="fld<?php echo $forum_page['fld_count'] ?>" name="output_calls" rows="15" cols="65"><?php echo $text_calls; ?></textarea></span>
					</label>	
					<label for="fld<?php echo ++$forum_page['fld_count'] ?>">
							<span class="fld-input"><textarea id="fld<?php echo $forum_page['fld_count'] ?>" name="output_entries" rows="15" cols="65"><?php echo $text_entries; ?></textarea></span>
					</label>
				</form>
			</div>
			
		</div>
		<?php
	return 0;
}

function pun_lang_checker_do_cache($text_calls, $text_entries)
{
	$pun_lang_checker_timestamp =  time();
	
	$fh = @fopen(FORUM_CACHE_DIR.'cache_lang_checker.php', 'wb');
	if (!$fh)
		error('Unable to write configuration cache file to cache directory. Please make sure PHP has write access to the directory \'cache\'.', __FILE__, __LINE__);
	else
		fwrite($fh, '<?php'."\n\n".'define(\'FORUM_LANG_CHECKER_LOADED\', 1);'."\n\n".'$text_calls = \''.$text_calls.'\';'."\n\n".'$text_entries = \''.$text_entries.'\';'."\n\n".'$pun_lang_checker_timestamp = '.$pun_lang_checker_timestamp.';'.'?>');

	fclose($fh);   
}

?>