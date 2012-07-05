<?php

/**
 * PunBB Repository extension functions file
 *
 * @copyright Copyright (C) 2008 - 2012 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_repository
 */

if (!defined('FORUM')) die();

//define('PUN_REPOSITORY_URL', 'http://home/extensions');
define('PUN_REPOSITORY_URL', 'http://punbb.informer.com/extensions/1.4');

// Refresh Pun Repository cache.
// Returns true on success, false otherwise. User $error for details.
function pun_repository_generate_cache(&$error)
{
	global $lang_pun_repository;

	// Download extensions list
	$remote_file = get_remote_file(PUN_REPOSITORY_URL.'/pun_repository.xml', 2);

	($hook = get_hook('pun_repository_generate_cache_get_remote_file')) ? eval($hook) : null;

	if (empty($remote_file) || empty($remote_file['content']))
	{
		$error = $lang_pun_repository['Can\'t access repository'];
		return false;
	}

	$info = xml_to_array($remote_file['content']);

	if (empty($info) || !isset($info['extensions']['extension']) || !is_array($info['extensions']['extension']))
	{
		$error = $lang_pun_repository['Can\'t access repository'];
		return false;
	}

	$extensions = array();
	foreach ($info['extensions']['extension'] as $ext)
		$extensions[$ext['id']] = $ext;

	($hook = get_hook('pun_repository_generate_cache_pre_fopen')) ? eval($hook) : null;

	// Output update status as PHP code
	$fh = @fopen(FORUM_CACHE_DIR.'cache_pun_repository.php', 'wb');
	if (!$fh)
	{
		$error = $lang_pun_repository['Can\'t write to cache file'];
		return false;
	}

	fwrite($fh, '<?php'."\n\n".
		'if (!defined(\'PUN_REPOSITORY_EXTENSIONS_LOADED\')) define(\'PUN_REPOSITORY_EXTENSIONS_LOADED\', 1);'."\n\n".
		'$pun_repository_extensions = '.var_export($extensions, true).';'."\n\n".
		'$pun_repository_extensions_timestamp = '.time().';'."\n\n".
		'?>');

	fclose($fh);

	return true;
}

// Remove directory recursively
function pun_repository_rm_recursive($file)
{
	if (is_file($file))
		return unlink($file);
	if (!is_dir($file))
		return true;
	$dir = opendir($file);
	while (($cur_file = readdir($dir)) !== false)
	{
		if ($cur_file == '.' || $cur_file == '..')
			continue;

		if (is_dir($file.'/'.$cur_file))
			pun_repository_rm_recursive($file.'/'.$cur_file);
		else
			unlink($file.'/'.$cur_file);
	}
	closedir($dir);
	rmdir($file);
}

// Download extension from remote repository
// Put extension data to $ext_data
// Returns error string on any problems or '' on success
function pun_repository_download_extension($ext_id, &$ext_data, $ext_path = FALSE)
{
	global $base_url, $lang_pun_repository;

	($hook = get_hook('pun_repository_download_extension_start')) ? eval($hook) : null;

	clearstatcache();

	if (!$ext_path)
	{
		$ext_path = FORUM_ROOT.'extensions/'.$ext_id;
		$extract_folder = FORUM_ROOT.'extensions/';
		$manifiest_path = $ext_path.'/manifest.xml';
	}
	else
	{
		$extract_folder = $ext_path;
		$manifiest_path = $ext_path.'/'.$ext_id.'/manifest.xml';
	}

	if (!is_dir($ext_path))
	{
		// Create new directory  with 777 mode
		if (@mkdir($ext_path) == false)
			return sprintf($lang_pun_repository['Can\'t create directory'], $ext_path);
		@chmod($ext_path, 0777);
	}
	else
		return sprintf($lang_pun_repository['Directory already exists'], $ext_path);

	// Download extension archive
	$pun_repository_archive = get_remote_file(PUN_REPOSITORY_URL.'/'.$ext_id.'/'.$ext_id.'.tgz', 10);
	if (empty($pun_repository_archive) || empty($pun_repository_archive['content']))
	{
		rmdir($ext_path);
		return $lang_pun_repository['Extension download failed'];
	}

	// Save extension to file
	$pun_repository_archive_file = @fopen(FORUM_ROOT.'extensions/'.$ext_id.'.tgz', 'wb');
	if ($pun_repository_archive_file === false)
		return $lang_pun_repository['No writting right'];

	fwrite($pun_repository_archive_file, $pun_repository_archive['content']);
	fclose($pun_repository_archive_file);

	if (!defined('PUN_REPOSITORY_TAR_EXTRACT_INCLUDED'))
		require 'pun_repository_tar_extract.php';

	// Extract files from archive
	$pun_repository_tar = new Archive_Tar_Ex(FORUM_ROOT.'extensions/'.$ext_id.'.tgz');
	if (!$pun_repository_tar->extract($extract_folder, 0777))
	{
		$error = $lang_pun_repository['Can\'t extract'];

		if (isset($pun_repository_tar->errors))
			$error .= ' '.$lang_pun_repository['Extract errors:'] . '<br />' . implode('<br />', $pun_repository_tar->errors);

		unlink(FORUM_ROOT.'extensions/'.$ext_id.'.tgz');
		@pun_repository_rm_recursive($ext_path);

		return $error;
	}

	// Remove archive
	unlink(FORUM_ROOT.'extensions/'.$ext_id.'.tgz');

	// Verify downloaded and extracted extension
	$ext_data = xml_to_array(@file_get_contents($manifiest_path));

	($hook = get_hook('pun_repository_download_extension_end')) ? eval($hook) : null;

	return '';
}

// Check for correct dependency ID's and get the list of unresolved dependencies
// $inst_exts must contain an array of installed extensions
// $dependencies['dependency'] must contain an array of extension ID's
// Unresolved dependencies are added to the $dependencies['unresolved'] array
function pun_repository_check_dependencies($inst_exts, $dependencies)
{
	($hook = get_hook('pun_repository_check_dependencies_start')) ? eval($hook) : null;

	if (empty($dependencies))
		return false;

	//print_r($dependencies);

	if (isset($dependencies['dependency']) && is_string($dependencies['dependency']))
		$dependencies = array($dependencies['dependency']);
	else
	{
		$dependencies = reset($dependencies);

		if (isset($dependencies['dependency']) && is_string($dependencies['dependency']))
			$dependencies = array($dependencies['dependency']);
		else
			$dependencies = $dependencies['dependency'];
	}

	$unresolved = array();

	foreach ($dependencies as $dependency)
	{
		$dependency = preg_replace('~[^\w_]~', '', $dependency);

		// Add the dependency to the list of unresolved ones
		if (!isset($inst_exts[$dependency]))
			$unresolved[] = $dependency;
	}

	($hook = get_hook('pun_repository_check_dependencies_end')) ? eval($hook) : null;

	return compact('dependencies', 'unresolved');
}

function pun_repository_dir_copy($src, $dest)
{
	$dir = opendir($src);
	if (!file_exists($dest))
		mkdir($dest);
	while (($file = readdir($dir)) !== false)
	{
		if ($file == '.' || $file == '..')
			continue;

		if (is_dir($src.'/'.$file))
			pun_repository_dir_copy($src.'/'.$file, $dest.'/'.$file);
		else
			copy($src.'/'.$file, $dest.'/'.$file);
	}
	closedir($dir);
}

