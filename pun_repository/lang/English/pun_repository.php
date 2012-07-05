<?php

/**
 * PunBB Repository extension language file
 *
 * @copyright Copyright (C) 2008 - 2012 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_repository
 */

if (!defined('FORUM')) die();

$lang_pun_repository = array(
	'Can\'t access repository'		=> '<strong>ERROR!</strong> PunBB Repository is inaccessible now. Try to check it later.',
	'PunBB Repository'				=> 'PunBB repository',
	'Files mode and owner'			=> '<strong>NOTE!</strong> Web server\'s system user will be set as an owner of the files and directories created while extension downloading and installation. Access mode for directories created will be set to 0777.',
	'Download and install'			=> 'Download and install extension',
	'Can\'t create directory'		=> '<strong>ERROR!</strong> Cannot create directory \'%s\'. Probably, directory \'extensions\' has not enough rights.',
	'Directory already exists'		=> '<strong>ERROR!</strong> Directory \'%s\' already exists.',
	'Extension download failed'		=> '<strong>ERROR!</strong> Extension download failed.',
	'No writting right'				=> '<strong>ERROR!</strong> Directory \'extensions\' has not enough rights to create a file.',
	'Can\'t extract'				=> '<strong>ERROR!</strong> Files could not be extracted from the downloaded archive.',
	'Download successful'			=> 'Archive download is successful. The extension is ready to be installed.',
	'Incorrect manifest.xml'		=> 'Archive download is uccessful, but manifest.xml is incorrect.',
	'Extract errors:'				=> 'The next errors appear while extracting:',
	'Direct download links:'		=> 'Direct download links:',
	'Can\'t write to cache file'	=> '<strong>ERROR!</strong> Cannot write a file to cache.',
	'All installed or downloaded'	=> '<strong>NOTE!</strong> You have installed or downloaded all available extensions. Congratulations!',
	'Download and update'			=> 'Download and update',
	'Unable to rename old dir'		=> '<strong>ERROR!</strong>. Unable to rename directory \'%s\' to update extension.',
	'Dependencies:'					=> 'Dependencies:',
	'Resolve dependencies:'			=> 'Please resolve next dependencies first to install this extension:',
	'Clear cache'					=> 'Clear cache',
	'Unable to remove cached file'	=> 'Unable to remove cached file.',
	'Cache has been successfully cleared' => 'Cache has been successfully cleared.',

	'Unsupported compression type'	=>	'Unsupported compression type',
	'Supported types are'			=>	'Supported types are \'gz\' and \'bz2\'',
	'The extension couldn\'t be found'	=>	'The extension \'%s\' couldn\'t be found',
	'Please make sure your version of PHP was built with'	=>	'Please make sure your version of PHP was built with \'%s\' support',
	'Invalid string list'			=>	'Invalid string list',
	'Unable to open in read mode'	=>	'Unable to open in read mode',
	'Unable to open in write mode'	=>	'Unable to open in write mode',
	'Unknown or missing compression type'	=>	'Unknown or missing compression type',
	'Invalid file descriptor'		=>	'Invalid file descriptor',
	'File does not exist'			=>	'File \'%s\' does not exist',
	'Directory can not be read'		=>	'Directory \'%s\' can not be read',
	'Invalid file name'				=>	'Invalid file name',
	'Unable to open file in binary read mode'	=>	'Unable to open file \'%s\' in binary read mode',
	'Invalid block size'			=>	'Invalid block size',
	'Invalid checksum for file'		=>	'Invalid checksum for file',
	'calculated'					=>	'calculated',
	'expected'						=>	'expected',
	'Malicious .tar detected, file'	=>	'Malicious .tar detected, file \' %s \' will not install in desired directory tree',
	'Invalid extract mode'			=>	'Invalid extract mode',
	'File already exists as a directory'	=>	'File \'%s\' already exists as a directory',
	'Directory already exists as a file'	=>	'Directory \'%s\' already exists as a file',
	'File already exists and is write protected'	=>	'File \'%s\' already exists and is write protected',
	'Unable to create path for'		=>	'Unable to create path for',
	'Unable to create directory'	=>	'Unable to create directory',
	'Unable to extract symbolic link'	=>	'Unable to extract symbolic link',
	'Error while opening {} in write binary mode'	=>	'Error while opening {\'%s\'} in write binary mode',
	'Extracted file does not have the correct file size'	=>	'Extracted file \'%s\' does not have the correct file size',
	'Archive may be corrupted'		=>	'Archive may be corrupted.',
	'Copy fail'						=>	'Can\'t copy new files of the extension to the old directory %s.',
);


