<?php

/**
 * pun_admin_manage_extensions_improved functions
 *
 * @copyright Copyright (C) 2009 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_admin_manage_extensions_improved
 */

if (!defined('FORUM')) die();

function get_dependencies_error_disable($sel_extens)
{
	global $forum_db;

	$query = array(
		'SELECT'	=> 'id, dependencies',
		'FROM'		=> 'extensions',
		'WHERE'		=> 'disabled = 0'
	);
	$res = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$ext_dependencies = array();
	if ($forum_db->num_rows($res) > 0)
		while ($cur_ext = $forum_db->fetch_assoc($res))
		{
			$deps = explode('|', substr($cur_ext['dependencies'], 1, -1));
			$ext_dependencies[$cur_ext['id']] = empty($deps[0]) ? null : $deps;
		}
	if (empty($ext_dependencies))
		return array();

	$dependencies_error = array();
	foreach ($ext_dependencies as $dep_ext => $main_exts)
	{
		if ($main_exts == null)
			continue;
		foreach ($main_exts as $cur_main_ext)
		{
			//If we want to disable main extension, added dependend extension to error list
			if (in_array($cur_main_ext, $sel_extens) && !in_array($dep_ext, $sel_extens))
			{
				if (empty($dependencies_error[$dep_ext]))
					$dependencies_error[$dep_ext] = array();
				$dependencies_error[$dep_ext][] = $cur_main_ext;
			}
		}
	}

	return $dependencies_error;
}

function get_dependencies_error_enable($sel_extens)
{
	global $forum_db;

	$query = array(
		'SELECT'	=> 'id, dependencies, disabled',
		'FROM'		=> 'extensions'
	);
	$res = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$ext_dependencies = array();
	$enabled_extensions = array();
	if ($forum_db->num_rows($res) > 0)
		while ($cur_ext = $forum_db->fetch_assoc($res))
		{
			$deps = explode('|', substr($cur_ext['dependencies'], 1, -1));
			$ext_dependencies[$cur_ext['id']] = empty($deps[0]) ? null : $deps;
			if ($cur_ext['disabled'] == '0')
				$enabled_extensions[] = $cur_ext['id'];
		}
	if (empty($ext_dependencies))
		return array();

	$dependencies_error = array();
	foreach ($sel_extens as $cur_ext)
	{
		if ($ext_dependencies[$cur_ext] == null)
			continue;
		foreach ($ext_dependencies[$cur_ext] as $main_ext)
		{
			if (in_array($main_ext, $enabled_extensions) || in_array($main_ext, $sel_extens))
				continue;
			if (empty($dependencies_error[$cur_ext]))
				$dependencies_error[$cur_ext] = array();
			$dependencies_error[$cur_ext][] = $main_ext;
		}
	}

	return $dependencies_error;
}

function get_dependencies_error_uninstall($sel_extens)
{
	global $forum_db;

	$query = array(
		'SELECT'	=> 'id, dependencies, disabled',
		'FROM'		=> 'extensions'
	);
	$res = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$ext_dependencies = array();
	if ($forum_db->num_rows($res) > 0)
		while ($cur_ext = $forum_db->fetch_assoc($res))
		{
			$deps = explode('|', substr($cur_ext['dependencies'], 1, -1));
			$ext_dependencies[$cur_ext['id']] = empty($deps[0]) ? null : $deps;
			if ($cur_ext['disabled'] == '0')
				$enabled_extensions[] = $cur_ext['id'];
		}
	if (empty($ext_dependencies))
		return array();

	$dependencies_error = array();
	foreach ($sel_extens as $cur_ext)
	{
		foreach ($ext_dependencies as $dep_ext => $main_exts)
		{
			if ($main_exts == null)
				continue;
			if (in_array($cur_ext, $main_exts))
			{
				if (empty($dependencies_error[$dep_ext]))
					$dependencies_error[$dep_ext] = array();
				$dependencies_error[$dep_ext][] = $cur_ext;
			}
		}
	}

	return $dependencies_error;
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
		'FROM'			=> 'config AS c'
	);

	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$forum_config = array();
	while ($cur_config_item = $forum_db->fetch_row($result))
		$forum_config[$cur_config_item[0]] = $cur_config_item[1];

	// Get hooks from the DB
	$query = array(
		'SELECT'		=> 'eh.id, eh.code, eh.extension_id',
		'FROM'			=> 'extension_hooks AS eh',
		'JOINS'			=> array(
			array(
				'INNER JOIN'	=> 'extensions AS e',
				'ON'			=> 'e.id=eh.extension_id'
			)
		),
		'WHERE'			=> 'e.disabled=0',
		'ORDER BY'		=> 'eh.priority, eh.installed'
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

function is_key_exists($arr, $keys)
{
	foreach ($keys as $key)
	{
		if (array_key_exists($key, $arr))
			return $key;
	}
	return false;
}

?>