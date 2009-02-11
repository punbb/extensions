<?php

/**
 * pun_admin_manage_extensions_improved functions
 *
 * @copyright Copyright (C) 2008 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_admin_manage_extensions_improved
 */

if (!defined('FORUM')) die();

	function get_active($sel_ext, $active)
	{
		global $forum_db;
		
		$imp_query = array(
			'SELECT'	=> 'id, disabled',
			'FROM'		=> 'extensions',
			'WHERE'		=> 'id IN ("'.implode('", "', $sel_ext).'")'
		);
		
		$imp_result = $forum_db->query_build($imp_query) or error(__FILE__, __LINE__);
		
		$act_ext = array();
		
		if ($forum_db->num_rows($imp_result))
		{
			while ($row = $forum_db->fetch_assoc($imp_result))
			{
				if ($row['disabled'] == $active)
					$act_ext[] = $row['id'];
			}
		}
	}
	
	function get_only_dep($main_ext)
	{
		global $forum_db;
		
		$query = array(
			'SELECT'	=> 'id, dependencies',
			'FROM'		=> 'extensions',
			'WHERE'		=> 'id IN ("'.implode('", "', $main_ext).'")'
		);
		
		$res = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		
		$deps = array();
		
		while ($cur_ext = $forum_db->fetch_assoc($res))
		{
			$deps[ $cur_ext['id'] ] = explode('|', substr($cur_ext['dependencies'], 1, -1));
			
			if (empty($deps[ $cur_ext['id'] ][0]))
				$deps[ $cur_ext['id'] ] = array();
		}
		
		$only_dep = array();
		
		foreach ($deps as $key => $text)
		{
			if (count($text) > 0)
				$only_dep = array_values($text);
		}
		
		return $only_dep;
	}
	
	function get_dependencies()
	{
		global $forum_db;
		
		$query = array(
			'SELECT'	=> 'id, dependencies',
			'FROM'		=> 'extensions'
		);
		
		$res = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		
		$deps = array();
		
		while ($cur_ext = $forum_db->fetch_assoc($res))
		{
			$deps[ $cur_ext['id'] ] = explode('|', substr($cur_ext['dependencies'], 1, -1));
			
			if (empty($deps[ $cur_ext['id'] ][0]))
				$deps[ $cur_ext['id'] ] = array();
		}
		
		return $deps;
	}
	
	function get_dependencies_list()
	{
		$dependencies = get_dependencies();
		
		//Get all disable extensions
		if (isset($_POST['extens']))
			$sel_arr = array_keys($_POST['extens']);
		else
			$sel_arr = explode(',', $_POST['selected_extens']);	
		
		$list = array();
		for ($sel_num = 0; $sel_num < count($sel_arr); $sel_num++)
		{
			$list[] = $sel_arr[$sel_num];
			foreach ($dependencies as $ext => $dep_list)
				if (in_array($sel_arr[$sel_num], $dep_list))
					$list[] = $ext;
		}
		$list = array_unique($list);
		
		return $list;
	}	

	function get_dependencies_list_rev()
	{
		$dependencies = get_dependencies();

		if (isset($_POST['extens']))
			$sel_arr = array_keys($_POST['extens']);
		else
			$sel_arr = explode(',', $_POST['selected_extens']);
		
		$list = array();
		for ($sel_num = 0; $sel_num < count($sel_arr); $sel_num++)
		{
			if ($dependencies[ $sel_arr[$sel_num] ] != array())
				for ($i = 0; $i < count($dependencies[ $sel_arr[$sel_num] ]); $i++)
					$list[] = $dependencies[ $sel_arr[$sel_num] ][ $i ];
			$list[] = $sel_arr[$sel_num];
		}		
		$list = array_unique($list);
		
		return $list;
	}	
	
	function uninstall_extensions( $uninst_list )
	{
		global $forum_db;
	
		for ($ext_num = 0; $ext_num < count($uninst_list); $ext_num++)	
		{			
			// Fetch info about the extension
			$query = array(
				'SELECT'	=> 'uninstall',
				'FROM'		=> 'extensions',
				'WHERE'		=> 'id = \''.$forum_db->escape( $uninst_list[$ext_num] ).'\''
			);
				
			$res = $forum_db->query_build($query) or error(__FILE__, __LINE__);
			if ( !$forum_db->num_rows($res) )
				continue;

			$ext_data = $forum_db->fetch_assoc($res);				
			eval($ext_data['uninstall']);

			// Now delete the extension and its hooks from the db
			$query = array(
				'DELETE'	=> 'extension_hooks',
				'WHERE'		=> 'extension_id = \''.$forum_db->escape( $uninst_list[$ext_num] ).'\''
			);

			($hook = get_hook('aex_qr_uninstall_delete_hooks')) ? eval($hook) : null;
			$forum_db->query_build($query) or error(__FILE__, __LINE__);

			$query = array(
				'DELETE'	=> 'extensions',
				'WHERE'		=> 'id = \''.$forum_db->escape( $uninst_list[$ext_num] ).'\''
			);

			($hook = get_hook('aex_qr_delete_extension')) ? eval($hook) : null;
			$forum_db->query_build($query) or error(__FILE__, __LINE__);				
				
			// Empty the PHP cache
			forum_clear_cache();

			// Regenerate the hooks cache
			require_once FORUM_ROOT.'include/cache.php';
			generate_hooks_cache();			
		}
		
		// Empty the PHP cache
		forum_clear_cache();

		// Regenerate the hooks cache
		require_once FORUM_ROOT.'include/cache.php';
		generate_hooks_cache();
	}
	
	function flip_extensions($type, $extensions)
	{
		global $forum_db;
		
		//First disable dependend extensions
		$query  = array(
			'UPDATE' => 'extensions',
			'SET'	=>	'disabled = \''.$type.'\'',
			'WHERE'	=>	'id IN (\''.implode('\',\'', $extensions).'\')'
		);
			
		$forum_db->query_build($query) or error(__FILE__, __LINE__);	
			
		// Regenerate the hooks cache
		require_once FORUM_ROOT.'include/cache.php';
		generate_hooks_cache();		
	}

	function validate_ext_list( $extensions )
	{
		$sel_extens = array();
		for ($sel_num = 0; $sel_num < count($extensions); $sel_num++)
			$sel_extens[$sel_num] = preg_replace('/[^0-9a-z_]/', '', $extensions[$sel_num]);
			
		return $sel_extens;
	}
	
	function regenerate_glob_vars()
	{
		global $forum_db, $forum_config, $forum_hooks;
	
		// Empty the PHP cache
		forum_clear_cache();

		// Config and hooks might be changed. Let's get them

		// Get the forum config from the DB
		$query = array(
			'SELECT'		=> 'c.*',
			'FROM'		  => 'config AS c'
		);

		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		$forum_config = array();
		while ($cur_config_item = $forum_db->fetch_row($result))
			$forum_config[$cur_config_item[0]] = $cur_config_item[1];

		// Get hooks from the DB
		$query = array(
			'SELECT'		=> 'eh.id, eh.code, eh.extension_id',
			'FROM'		  => 'extension_hooks AS eh',
			'JOINS'		 => array(
				array(
					'INNER JOIN'	=> 'extensions AS e',
					'ON'			=> 'e.id=eh.extension_id'
				)
			),
			'WHERE'		 => 'e.disabled=0',
			'ORDER BY'	  => 'eh.priority, eh.installed'
		);

		($hook = get_hook('ch_qr_get_hooks')) ? eval($hook) : null;
	
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		$forum_hooks = array();
		while ($cur_hook = $forum_db->fetch_assoc($result))
		{
			$load_ext_info = '$ext_info_stack[] = array('."\n".
				'\'id\'		 => \''.$cur_hook['extension_id'].'\','."\n".
				'\'path\'	   => FORUM_ROOT.\'extensions/'.$cur_hook['extension_id'].'\','."\n".
				'\'url\'		=> $GLOBALS[\'base_url\'].\'/extensions/'.$cur_hook['extension_id'].'\');'."\n".'
				$ext_info = $ext_info_stack[count($ext_info_stack) - 1];';
				$unload_ext_info = 'array_pop($ext_info_stack);'."\n".'$ext_info = empty($ext_info_stack) ? array() : $ext_info_stack[count($ext_info_stack) - 1];';

			$forum_hooks[$cur_hook['id']][] = $load_ext_info."\n\n".$cur_hook['code']."\n\n".$unload_ext_info."\n";
		}
	}

?>