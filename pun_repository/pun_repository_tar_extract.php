<?php

define('PUN_REPOSITORY_TAR_EXTRACT_INCLUDED', true);

// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 3.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available through the world-wide-web at the following url:           |
// | http://www.php.net/license/3_0.txt.                                  |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Author: Vincent Blavet <vincent@phpconcept.net>                      |
// +----------------------------------------------------------------------+

//
// Modified by PunBB team
//

/**
* Creates a (compressed) Tar archive
*
* @author   Vincent Blavet <vincent@phpconcept.net>
* @version  $Revision: 1.39 $
* @package  Archive
*/
class Archive_Tar_Ex
{
	var $_tarname='';
	var $_compress=false;
	var $_compress_type='none';
	var $_separator=' ';
	var $_file=0;
	var $_temp_tarname='';
	var $_default_right = false;
	var $errors = array();

	function Archive_Tar_Ex($p_tarname, $p_compress = null)
	{
		global $lang_pun_repository;
		$this->_compress = false;
		$this->_compress_type = 'none';
		if (($p_compress === null) || ($p_compress == '')) {
			if (@file_exists($p_tarname)) {
				if ($fp = @fopen($p_tarname, "rb")) {
					// look for gzip magic cookie
					$data = fread($fp, 2);
					fclose($fp);
					if ($data == "\37\213") {
						$this->_compress = true;
						$this->_compress_type = 'gz';
					// No sure it's enought for a magic code ....
					} elseif ($data == "BZ") {
						$this->_compress = true;
						$this->_compress_type = 'bz2';
					}
				}
			} else {
				// probably a remote file or some file accessible
				// through a stream interface
				if (substr($p_tarname, -2) == 'gz') {
					$this->_compress = true;
					$this->_compress_type = 'gz';
				} elseif ((substr($p_tarname, -3) == 'bz2') ||
						(substr($p_tarname, -2) == 'bz')) {
					$this->_compress = true;
					$this->_compress_type = 'bz2';
				}
			}
		} else {
			if (($p_compress === true) || ($p_compress == 'gz')) {
				$this->_compress = true;
				$this->_compress_type = 'gz';
			} else if ($p_compress == 'bz2') {
				$this->_compress = true;
				$this->_compress_type = 'bz2';
			} else {
				die($lang_pun_repository['Unsupported compression type']." '$p_compress'\n".
					$lang_pun_repository['Supported types are'].".\n");
				return false;
			}
		}
		$this->_tarname = $p_tarname;
		if ($this->_compress) { // assert zlib or bz2 extension support
			if ($this->_compress_type == 'gz')
				$extname = 'zlib';
			else if ($this->_compress_type == 'bz2')
				$extname = 'bz2';
			if (!extension_loaded($extname)) {
				die(sprintf($lang_pun_repository['The extension couldn\'t be found'],$extname)."\n".
					sprintf($lang_pun_repository['Please make sure your version of PHP was built with'],$extname)."\n");
				return false;
			}
		}
	}

	function _Archive_Tar_Ex()
	{
		$this->_close();
		// ----- Look for a local copy to delete
		if ($this->_temp_tarname != '')
			@unlink($this->_temp_tarname);
	}

	function extract($p_path='', $right = false)
	{
		$this->_default_right = $right;
		return $this->extractModify($p_path, '');
	}

	function extractModify($p_path, $p_remove_path)
	{
		$v_result = true;
		$v_list_detail = array();

		if ($v_result = $this->_openRead()) {
			$v_result = $this->_extractList($p_path, $v_list_detail,
											"complete", 0, $p_remove_path);
			$this->_close();
		}

		return $v_result;
	}

	function extractList($p_filelist, $p_path='', $p_remove_path='')
	{
		global $lang_pun_repository;
		$v_result = true;
		$v_list_detail = array();

		if (is_array($p_filelist))
			$v_list = $p_filelist;
		elseif (is_string($p_filelist))
			$v_list = explode($this->_separator, $p_filelist);
		else {
			$this->_error($lang_pun_repository['Invalid string list']);
			return false;
		}

		if ($v_result = $this->_openRead()) {
			$v_result = $this->_extractList($p_path, $v_list_detail, "partial",
											$v_list, $p_remove_path);
			$this->_close();
		}

		return $v_result;
	}

	function _error($p_message)
	{
		$this->errors[] = $p_message;
	}

	function _warning($p_message)
	{
		$this->errors[] = $p_message;
	}

	function _isArchive($p_filename=NULL)
	{
		if ($p_filename == NULL) {
			$p_filename = $this->_tarname;
		}
		clearstatcache();
		return @is_file($p_filename);
	}

	function _openRead()
	{
		global $lang_pun_repository;
		if (strtolower(substr($this->_tarname, 0, 7)) == 'http://') {

		// ----- Look if a local copy need to be done
		if ($this->_temp_tarname == '') {
			$this->_temp_tarname = uniqid('tar').'.tmp';
			if (!$v_file_from = @fopen($this->_tarname, 'rb')) {
				$this->_error($lang_pun_repository['Unable to open in read mode'].' \''
							.$this->_tarname.'\'');
				$this->_temp_tarname = '';
				return false;
			}
			if (!$v_file_to = @fopen($this->_temp_tarname, 'wb')) {
				$this->_error($lang_pun_repository['Unable to open in write mode'].' \''
							.$this->_temp_tarname.'\'');
				$this->_temp_tarname = '';
				return false;
			}
			while ($v_data = @fread($v_file_from, 1024))
				@fwrite($v_file_to, $v_data);
			@fclose($v_file_from);
			@fclose($v_file_to);
		}

		// ----- File to open if the local copy
		$v_filename = $this->_temp_tarname;

		} else
		// ----- File to open if the normal Tar file
		$v_filename = $this->_tarname;

		if ($this->_compress_type == 'gz')
			$this->_file = @gzopen($v_filename, "rb");
		else if ($this->_compress_type == 'bz2')
			$this->_file = @bzopen($v_filename, "rb");
		else if ($this->_compress_type == 'none')
			$this->_file = @fopen($v_filename, "rb");
		else
			$this->_error($lang_pun_repository['Unknown or missing compression type'].' ('
						.$this->_compress_type.')');

		if ($this->_file == 0) {
			$this->_error($lang_pun_repository['Unable to open in read mode'].' \''.$v_filename.'\'');
			return false;
		}

		return true;
	}

	function _close()
	{
		global $lang_pun_repository;
		if (is_resource($this->_file)) {
			if ($this->_compress_type == 'gz')
				@gzclose($this->_file);
			else if ($this->_compress_type == 'bz2')
				@bzclose($this->_file);
			else if ($this->_compress_type == 'none')
				@fclose($this->_file);
			else
				$this->_error($lang_pun_repository['Unknown or missing compression type'].' ('
							.$this->_compress_type.')');

			$this->_file = 0;
		}

		// ----- Look if a local copy need to be erase
		// Note that it might be interesting to keep the url for a time : ToDo
		if ($this->_temp_tarname != '') {
			@unlink($this->_temp_tarname);
			$this->_temp_tarname = '';
		}

		return true;
	}

	function _cleanFile()
	{
		$this->_close();

		// ----- Look for a local copy
		if ($this->_temp_tarname != '') {
			// ----- Remove the local copy but not the remote tarname
			@unlink($this->_temp_tarname);
			$this->_temp_tarname = '';
		} else {
			// ----- Remove the local tarname file
			@unlink($this->_tarname);
		}
		$this->_tarname = '';

		return true;
	}

	function _writeBlock($p_binary_data, $p_len=null)
	{
		global $lang_pun_repository;
		if (is_resource($this->_file)) {
			if ($p_len === null) {
				if ($this->_compress_type == 'gz')
					@gzputs($this->_file, $p_binary_data);
				else if ($this->_compress_type == 'bz2')
					@bzwrite($this->_file, $p_binary_data);
				else if ($this->_compress_type == 'none')
					@fputs($this->_file, $p_binary_data);
				else
					$this->_error($lang_pun_repository['Unknown or missing compression type'].' ('
									.$this->_compress_type.')');
			} else {
				if ($this->_compress_type == 'gz')
					@gzputs($this->_file, $p_binary_data, $p_len);
				else if ($this->_compress_type == 'bz2')
					@bzwrite($this->_file, $p_binary_data, $p_len);
				else if ($this->_compress_type == 'none')
					@fputs($this->_file, $p_binary_data, $p_len);
				else
					$this->_error($lang_pun_repository['Unknown or missing compression type'].' ('
									.$this->_compress_type.')');

			}
		}
		return true;
	}

	function _readBlock()
	{
		global $lang_pun_repository;
		$v_block = null;
		if (is_resource($this->_file)) {
			if ($this->_compress_type == 'gz')
				$v_block = @gzread($this->_file, 512);
			else if ($this->_compress_type == 'bz2')
				$v_block = @bzread($this->_file, 512);
			else if ($this->_compress_type == 'none')
				$v_block = @fread($this->_file, 512);
			else
				$this->_error($lang_pun_repository['Unknown or missing compression type'].' ('
								.$this->_compress_type.')');
		}
		return $v_block;
	}

	function _jumpBlock($p_len=null)
	{
	global $lang_pun_repository;
		if (is_resource($this->_file)) {
			if ($p_len === null)
				$p_len = 1;

			if ($this->_compress_type == 'gz') {
				@gzseek($this->_file, gztell($this->_file)+($p_len*512));
			}
			else if ($this->_compress_type == 'bz2') {
				// ----- Replace missing bztell() and bzseek()
				for ($i=0; $i<$p_len; $i++)
					$this->_readBlock();
			} else if ($this->_compress_type == 'none')
				@fseek($this->_file, ftell($this->_file)+($p_len*512));
			else
				$this->_error($lang_pun_repository['Unknown or missing compression type'].' ('
								.$this->_compress_type.')');

		}
		return true;
	}

	function _writeFooter()
	{
		if (is_resource($this->_file)) {
			// ----- Write the last 0 filled block for end of archive
			$v_binary_data = pack('a1024', '');
			$this->_writeBlock($v_binary_data);
		}
		return true;
	}

	function _addList($p_list, $p_add_dir, $p_remove_dir)
	{
		global $lang_pun_repository;
		$v_result=true;
		$v_header = array();

		// ----- Remove potential windows directory separator
		$p_add_dir = $this->_translateWinPath($p_add_dir);
		$p_remove_dir = $this->_translateWinPath($p_remove_dir, false);

		if (!$this->_file) {
			$this->_error($lang_pun_repository['Invalid file descriptor']);
			return false;
		}

		if (sizeof($p_list) == 0)
			return true;

		foreach ($p_list as $v_filename) {
			if (!$v_result) {
				break;
			}

			// ----- Skip the current tar name
			if ($v_filename == $this->_tarname)
				continue;

			if ($v_filename == '')
				continue;

			if (!file_exists($v_filename)) {
				$this->_warning(sprintf($lang_pun_repository['File does not exist'],$v_filename));
				continue;
			}

			// ----- Add the file or directory header
			if (!$this->_addFile($v_filename, $v_header, $p_add_dir, $p_remove_dir))
				return false;

			if (@is_dir($v_filename)) {
				if (!($p_hdir = opendir($v_filename))) {
					$this->_warning(sprintf($lang_pun_repository['Directory can not be read'],$v_filename));
					continue;
				}
				while (false !== ($p_hitem = readdir($p_hdir))) {
					if (($p_hitem != '.') && ($p_hitem != '..')) {
						if ($v_filename != ".")
							$p_temp_list[0] = $v_filename.'/'.$p_hitem;
						else
							$p_temp_list[0] = $p_hitem;

						$v_result = $this->_addList($p_temp_list,
													$p_add_dir,
													$p_remove_dir);
					}
				}

				unset($p_temp_list);
				unset($p_hdir);
				unset($p_hitem);
			}
		}

		return $v_result;
	}

	function _addFile($p_filename, &$p_header, $p_add_dir, $p_remove_dir)
	{
		global $lang_pun_repository;
		if (!$this->_file) {
			$this->_error($lang_pun_repository['Invalid file descriptor']);
			return false;
		}

		if ($p_filename == '') {
			$this->_error($lang_pun_repository['Invalid file name']);
			return false;
		}

		$p_filename = $this->_translateWinPath($p_filename, false);;
		$v_stored_filename = $p_filename;
		if (strcmp($p_filename, $p_remove_dir) == 0) {
			return true;
		}
		if ($p_remove_dir != '') {
			if (substr($p_remove_dir, -1) != '/')
				$p_remove_dir .= '/';

			if (substr($p_filename, 0, strlen($p_remove_dir)) == $p_remove_dir)
				$v_stored_filename = substr($p_filename, strlen($p_remove_dir));
		}
		$v_stored_filename = $this->_translateWinPath($v_stored_filename);
		if ($p_add_dir != '') {
			if (substr($p_add_dir, -1) == '/')
				$v_stored_filename = $p_add_dir.$v_stored_filename;
			else
				$v_stored_filename = $p_add_dir.'/'.$v_stored_filename;
		}

		$v_stored_filename = $this->_pathReduction($v_stored_filename);

		if ($this->_isArchive($p_filename)) {
			if (($v_file = @fopen($p_filename, "rb")) == 0) {
				$this->_warning(sprintf($lang_pun_repository['Unable to open file in binary read mode'],$p_filename));
				return true;
			}

			if (!$this->_writeHeader($p_filename, $v_stored_filename))
				return false;

			while (($v_buffer = fread($v_file, 512)) != '') {
				$v_binary_data = pack("a512", "$v_buffer");
				$this->_writeBlock($v_binary_data);
			}

			fclose($v_file);

		} else {
			// ----- Only header for dir
			if (!$this->_writeHeader($p_filename, $v_stored_filename))
				return false;
		}

		return true;
	}

	function _writeHeader($p_filename, $p_stored_filename)
	{
		if ($p_stored_filename == '')
			$p_stored_filename = $p_filename;
		$v_reduce_filename = $this->_pathReduction($p_stored_filename);

		if (strlen($v_reduce_filename) > 99) {
		if (!$this->_writeLongHeader($v_reduce_filename))
			return false;
		}

		$v_info = stat($p_filename);
		$v_uid = sprintf("%6s ", DecOct($v_info[4]));
		$v_gid = sprintf("%6s ", DecOct($v_info[5]));
		$v_perms = sprintf("%6s ", DecOct(fileperms($p_filename)));

		$v_mtime = sprintf("%11s", DecOct(filemtime($p_filename)));

		if (@is_dir($p_filename)) {
		$v_typeflag = "5";
		$v_size = sprintf("%11s ", DecOct(0));
		} else {
		$v_typeflag = '';
		clearstatcache();
		$v_size = sprintf("%11s ", DecOct(filesize($p_filename)));
		}

		$v_linkname = '';

		$v_magic = '';

		$v_version = '';

		$v_uname = '';

		$v_gname = '';

		$v_devmajor = '';

		$v_devminor = '';

		$v_prefix = '';

		$v_binary_data_first = pack("a100a8a8a8a12A12",
									$v_reduce_filename, $v_perms, $v_uid,
									$v_gid, $v_size, $v_mtime);
		$v_binary_data_last = pack("a1a100a6a2a32a32a8a8a155a12",
								$v_typeflag, $v_linkname, $v_magic,
								$v_version, $v_uname, $v_gname,
								$v_devmajor, $v_devminor, $v_prefix, '');

		// ----- Calculate the checksum
		$v_checksum = 0;
		// ..... First part of the header
		for ($i=0; $i<148; $i++)
			$v_checksum += ord(substr($v_binary_data_first,$i,1));
		// ..... Ignore the checksum value and replace it by ' ' (space)
		for ($i=148; $i<156; $i++)
			$v_checksum += ord(' ');
		// ..... Last part of the header
		for ($i=156, $j=0; $i<512; $i++, $j++)
			$v_checksum += ord(substr($v_binary_data_last,$j,1));

		// ----- Write the first 148 bytes of the header in the archive
		$this->_writeBlock($v_binary_data_first, 148);

		// ----- Write the calculated checksum
		$v_checksum = sprintf("%6s ", DecOct($v_checksum));
		$v_binary_data = pack("a8", $v_checksum);
		$this->_writeBlock($v_binary_data, 8);

		// ----- Write the last 356 bytes of the header in the archive
		$this->_writeBlock($v_binary_data_last, 356);

		return true;
	}

	function _writeLongHeader($p_filename)
	{
		$v_size = sprintf("%11s ", DecOct(strlen($p_filename)));

		$v_typeflag = 'L';

		$v_linkname = '';

		$v_magic = '';

		$v_version = '';

		$v_uname = '';

		$v_gname = '';

		$v_devmajor = '';

		$v_devminor = '';

		$v_prefix = '';

		$v_binary_data_first = pack("a100a8a8a8a12A12",
									'././@LongLink', 0, 0, 0, $v_size, 0);
		$v_binary_data_last = pack("a1a100a6a2a32a32a8a8a155a12",
								$v_typeflag, $v_linkname, $v_magic,
								$v_version, $v_uname, $v_gname,
								$v_devmajor, $v_devminor, $v_prefix, '');

		// ----- Calculate the checksum
		$v_checksum = 0;
		// ..... First part of the header
		for ($i=0; $i<148; $i++)
			$v_checksum += ord(substr($v_binary_data_first,$i,1));
		// ..... Ignore the checksum value and replace it by ' ' (space)
		for ($i=148; $i<156; $i++)
			$v_checksum += ord(' ');
		// ..... Last part of the header
		for ($i=156, $j=0; $i<512; $i++, $j++)
			$v_checksum += ord(substr($v_binary_data_last,$j,1));

		// ----- Write the first 148 bytes of the header in the archive
		$this->_writeBlock($v_binary_data_first, 148);

		// ----- Write the calculated checksum
		$v_checksum = sprintf("%6s ", DecOct($v_checksum));
		$v_binary_data = pack("a8", $v_checksum);
		$this->_writeBlock($v_binary_data, 8);

		// ----- Write the last 356 bytes of the header in the archive
		$this->_writeBlock($v_binary_data_last, 356);

		// ----- Write the filename as content of the block
		$i=0;
		while (($v_buffer = substr($p_filename, (($i++)*512), 512)) != '') {
			$v_binary_data = pack("a512", "$v_buffer");
			$this->_writeBlock($v_binary_data);
		}

		return true;
	}

	function _readHeader($v_binary_data, &$v_header)
	{
	global $lang_pun_repository;
		if (strlen($v_binary_data)==0) {
			$v_header['filename'] = '';
			return true;
		}

		if (strlen($v_binary_data) != 512) {
			$v_header['filename'] = '';
			$this->_error($lang_pun_repository['Invalid block size'].' : '.strlen($v_binary_data));
			return false;
		}

		if (!is_array($v_header)) {
			$v_header = array();
		}
		// ----- Calculate the checksum
		$v_checksum = 0;
		// ..... First part of the header
		for ($i=0; $i<148; $i++)
			$v_checksum+=ord(substr($v_binary_data,$i,1));
		// ..... Ignore the checksum value and replace it by ' ' (space)
		for ($i=148; $i<156; $i++)
			$v_checksum += ord(' ');
		// ..... Last part of the header
		for ($i=156; $i<512; $i++)
		$v_checksum+=ord(substr($v_binary_data,$i,1));

		$v_data = unpack("a100filename/a8mode/a8uid/a8gid/a12size/a12mtime/"
						."a8checksum/a1typeflag/a100link/a6magic/a2version/"
						."a32uname/a32gname/a8devmajor/a8devminor",
						$v_binary_data);

		// ----- Extract the checksum
		$v_header['checksum'] = OctDec(trim($v_data['checksum']));
		if ($v_header['checksum'] != $v_checksum) {
			$v_header['filename'] = '';

			// ----- Look for last block (empty block)
			if (($v_checksum == 256) && ($v_header['checksum'] == 0))
				return true;

			$this->_error($lang_pun_repository['Invalid checksum for file'].' "'.$v_data['filename']
						.'" : '.$v_checksum.' '.$lang_pun_repository['calculated'].', '
						.$v_header['checksum'].' '.$lang_pun_repository['expected']);
			return false;
		}

		// ----- Extract the properties
		$v_header['filename'] = trim($v_data['filename']);
		if ($this->_maliciousFilename($v_header['filename'])) {
			$this->_error(sprintf($lang_pun_repository['Malicious .tar detected, file'],$v_header['filename']));
			return false;
		}
		$v_header['mode'] = OctDec(trim($v_data['mode']));
		$v_header['uid'] = OctDec(trim($v_data['uid']));
		$v_header['gid'] = OctDec(trim($v_data['gid']));
		$v_header['size'] = OctDec(trim($v_data['size']));
		$v_header['mtime'] = OctDec(trim($v_data['mtime']));
		if (($v_header['typeflag'] = $v_data['typeflag']) == "5") {
		$v_header['size'] = 0;
		}
		$v_header['link'] = trim($v_data['link']);

		return true;
	}

	function _maliciousFilename($file)
	{
		if (strpos($file, '/../') !== false) {
			return true;
		}
		if (strpos($file, '../') === 0) {
			return true;
		}
		return false;
	}

	function _readLongHeader(&$v_header)
	{
		global $lang_pun_repository;
		$v_filename = '';
		$n = floor($v_header['size']/512);
		for ($i=0; $i<$n; $i++) {
			$v_content = $this->_readBlock();
			$v_filename .= $v_content;
		}
		if (($v_header['size'] % 512) != 0) {
			$v_content = $this->_readBlock();
			$v_filename .= $v_content;
		}

		// ----- Read the next header
		$v_binary_data = $this->_readBlock();

		if (!$this->_readHeader($v_binary_data, $v_header))
			return false;

		$v_header['filename'] = $v_filename;
			if ($this->_maliciousFilename($v_filename)) {
				$this->_error(sprintf($lang_pun_repository['Malicious .tar detected, file'],$v_filename));
				return false;
		}

		return true;
	}

	function _extractList($p_path, &$p_list_detail, $p_mode,
						$p_file_list, $p_remove_path)
	{
		global $lang_pun_repository;
		$v_result=true;
		$v_nb = 0;
		$v_extract_all = true;
		$v_listing = false;

		$p_path = $this->_translateWinPath($p_path, false);
		if ($p_path == '' || (substr($p_path, 0, 1) != '/'
			&& substr($p_path, 0, 3) != "../" && !strpos($p_path, ':'))) {
		$p_path = "./".$p_path;
		}
		$p_remove_path = $this->_translateWinPath($p_remove_path);

		// ----- Look for path to remove format (should end by /)
		if (($p_remove_path != '') && (substr($p_remove_path, -1) != '/'))
		$p_remove_path .= '/';
		$p_remove_path_size = strlen($p_remove_path);

		switch ($p_mode) {
			case "complete" :
				$v_extract_all = TRUE;
				$v_listing = FALSE;
			break;
			case "partial" :
				$v_extract_all = FALSE;
				$v_listing = FALSE;
			break;
			case "list" :
				$v_extract_all = FALSE;
				$v_listing = TRUE;
			break;
			default :
				$this->_error($lang_pun_repository['Invalid extract mode'].' ('.$p_mode.')');
				return false;
		}

		clearstatcache();

		while (strlen($v_binary_data = $this->_readBlock()) != 0)
		{
			$v_extract_file = FALSE;
			$v_extraction_stopped = 0;

			if (!$this->_readHeader($v_binary_data, $v_header))
				return false;

			if ($v_header['filename'] == '') {
				continue;
			}

			// ----- Look for long filename
			if ($v_header['typeflag'] == 'L') {
				if (!$this->_readLongHeader($v_header))
				return false;
			}

			if ((!$v_extract_all) && (is_array($p_file_list))) {
				// ----- By default no unzip if the file is not found
				$v_extract_file = false;

				for ($i=0; $i<sizeof($p_file_list); $i++) {
				// ----- Look if it is a directory
				if (substr($p_file_list[$i], -1) == '/') {
					// ----- Look if the directory is in the filename path
					if ((strlen($v_header['filename']) > strlen($p_file_list[$i]))
						&& (substr($v_header['filename'], 0, strlen($p_file_list[$i]))
							== $p_file_list[$i])) {
					$v_extract_file = TRUE;
					break;
					}
				}

				// ----- It is a file, so compare the file names
				elseif ($p_file_list[$i] == $v_header['filename']) {
					$v_extract_file = TRUE;
					break;
				}
				}
			} else {
				$v_extract_file = TRUE;
			}

			// ----- Look if this file need to be extracted
			if (($v_extract_file) && (!$v_listing))
			{
				if (($p_remove_path != '')
					&& (substr($v_header['filename'], 0, $p_remove_path_size)
						== $p_remove_path))
				$v_header['filename'] = substr($v_header['filename'],
												$p_remove_path_size);
				if (($p_path != './') && ($p_path != '/')) {
				while (substr($p_path, -1) == '/')
					$p_path = substr($p_path, 0, strlen($p_path)-1);

				if (substr($v_header['filename'], 0, 1) == '/')
					$v_header['filename'] = $p_path.$v_header['filename'];
				else
					$v_header['filename'] = $p_path.'/'.$v_header['filename'];
				}
				if (file_exists($v_header['filename'])) {
				if (   (@is_dir($v_header['filename']))
					&& ($v_header['typeflag'] == '')) {
					$this->_error(sprintf($lang_pun_repository['File already exists as a directory'],$v_header['filename']));
					return false;
				}
				if (   ($this->_isArchive($v_header['filename']))
					&& ($v_header['typeflag'] == "5")) {
					$this->_error(sprintf($lang_pun_repository['Directory already exists as a file'],$v_header['filename']));
					return false;
				}
				if (!is_writeable($v_header['filename'])) {
					$this->_error(sprintf($lang_pun_repository['File already exists and is write protected'],$v_header['filename']));
					return false;
				}
				if (filemtime($v_header['filename']) > $v_header['mtime']) {
					// To be completed : An error or silent no replace ?
				}
				}

				// ----- Check the directory availability and create it if necessary
				elseif (($v_result
						= $this->_dirCheck(($v_header['typeflag'] == "5"
											?$v_header['filename']
											:dirname($v_header['filename'])))) != 1) {
					$this->_error($lang_pun_repository['Unable to create path for'].' '.$v_header['filename']);
					return false;
				}

				if ($v_extract_file) {
				if ($v_header['typeflag'] == "5") {
					if (!@file_exists($v_header['filename'])) {
						if (!@mkdir($v_header['filename'], 0777)) {
							$this->_error($lang_pun_repository['Unable to create directory'].' {'
										.$v_header['filename'].'}');
							return false;
						}
					}
				} elseif ($v_header['typeflag'] == "2") {
					if (!@symlink($v_header['link'], $v_header['filename'])) {
						$this->_error($lang_pun_repository['Unable to extract symbolic link'].' {'
										.$v_header['filename'].'}');
						return false;
					}
				} else {
					if (($v_dest_file = @fopen($v_header['filename'], "wb")) == 0) {
						$this->_error(sprintf($lang_pun_repository['Error while opening {} in write binary mode'],$v_header['filename']));
						return false;
					} else {
						$n = floor($v_header['size']/512);
						for ($i=0; $i<$n; $i++) {
							$v_content = $this->_readBlock();
							fwrite($v_dest_file, $v_content, 512);
						}
					if (($v_header['size'] % 512) != 0) {
					$v_content = $this->_readBlock();
					fwrite($v_dest_file, $v_content, ($v_header['size'] % 512));
					}

					@fclose($v_dest_file);

					// ----- Change the file mode, mtime
					@touch($v_header['filename'], $v_header['mtime']);
					if ($v_header['mode'] & 0111) {
						// make file executable, obey umask
						$mode = fileperms($v_header['filename']) | (~umask() & 0111);
						if ($this->_default_right === false)
							@chmod($v_header['filename'], $mode);
						else
							@chmod($v_header['filename'], _default_right);
					}
				}

				// ----- Check the file size
				clearstatcache();
				if (filesize($v_header['filename']) != $v_header['size']) {
					$this->_error(sprintf($lang_pun_repository['Extracted file does not have the correct file size'],$v_header['filename']).' \''
									.filesize($v_header['filename'])
									.'\' ('.$v_header['size']
									.' '.$lang_pun_repository['Archive may be corrupted']);
					return false;
				}
				}
				} else {
				$this->_jumpBlock(ceil(($v_header['size']/512)));
				}
			} else {
				$this->_jumpBlock(ceil(($v_header['size']/512)));
			}

			if ($v_listing || $v_extract_file || $v_extraction_stopped) {
				// ----- Log extracted files
				if (($v_file_dir = dirname($v_header['filename']))
					== $v_header['filename'])
				$v_file_dir = '';
				if ((substr($v_header['filename'], 0, 1) == '/') && ($v_file_dir == ''))
				$v_file_dir = '/';

				$p_list_detail[$v_nb++] = $v_header;
				if (is_array($p_file_list) && (count($p_list_detail) == count($p_file_list))) {
					return true;
				}
			}
		}

		return true;
	}

	function _dirCheck($p_dir)
	{
		global $lang_pun_repository;
		clearstatcache();
		if ((@is_dir($p_dir)) || ($p_dir == ''))
			return true;

		$p_parent_dir = dirname($p_dir);

		if (($p_parent_dir != $p_dir) &&
			($p_parent_dir != '') &&
			(!$this->_dirCheck($p_parent_dir)))
			return false;

		if (!@mkdir($p_dir, 0777)) {
			$this->_error($lang_pun_repository['Unable to create directory']." '$p_dir'");
			return false;
		}
		else
			if ($this->_default_right !== false)
				@chmod($p_dir, 0777);

		return true;
	}

	function _pathReduction($p_dir)
	{
		$v_result = '';

		// ----- Look for not empty path
		if ($p_dir != '') {
			// ----- Explode path by directory names
			$v_list = explode('/', $p_dir);

			// ----- Study directories from last to first
			for ($i=sizeof($v_list)-1; $i>=0; $i--) {
				// ----- Look for current path
				if ($v_list[$i] == ".") {
					// ----- Ignore this directory
					// Should be the first $i=0, but no check is done
				}
				else if ($v_list[$i] == "..") {
					// ----- Ignore it and ignore the $i-1
					$i--;
				}
				else if (   ($v_list[$i] == '')
						&& ($i!=(sizeof($v_list)-1))
						&& ($i!=0)) {
					// ----- Ignore only the double '//' in path,
					// but not the first and last /
				} else {
					$v_result = $v_list[$i].($i!=(sizeof($v_list)-1)?'/'
								.$v_result:'');
				}
			}
		}
		$v_result = strtr($v_result, '\\', '/');
		return $v_result;
	}

	function _translateWinPath($p_path, $p_remove_disk_letter=true)
	{
		if (defined('OS_WINDOWS') && OS_WINDOWS) {
			// ----- Look for potential disk letter
			if (   ($p_remove_disk_letter)
				&& (($v_position = strpos($p_path, ':')) != false)) {
				$p_path = substr($p_path, $v_position+1);
			}
			// ----- Change potential windows directory separator
			if ((strpos($p_path, '\\') > 0) || (substr($p_path, 0,1) == '\\')) {
				$p_path = strtr($p_path, '\\', '/');
			}
		}
		return $p_path;
	}
}
?>
