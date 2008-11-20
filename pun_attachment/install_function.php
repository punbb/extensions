<?php

/***********************************************************************

	Copyright (C) 2008  PunBB

	Partially based on Attachment Mod by Frank Hagstrom

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

if (!defined('FORUM')) exit;

function attach_create_subfolder($subfolder, $basepath)
{
	global $ext_info, $forum_user, $lang_attach;
	if(!is_dir($basepath.$subfolder))
	{
		// if the folder doesn't excist, try to create it
		if(!mkdir($basepath.$subfolder,0750))
			error($lang_attach['Error: mkdir'].' \''.$basepath.$subfolder.'\' '.$lang_attach['Error: 0750'],__FILE__,__LINE__);
		// create a .htaccess and index.html file in the new subfolder
		if(!copy($basepath.'.htaccess', $basepath.$subfolder.'/.htaccess'))
			error($lang_attach['Error: .htaccess'].' \''.$basepath.$subfolder.'\'',__FILE__,__LINE__);
		if(!copy($basepath.'index.html', $basepath.$subfolder.'/index.html'))
			error($lang_attach['Error: index.html'].' \''.$basepath.$subfolder.'\'',__FILE__,__LINE__);
		// if the folder was created continue
	}
	// return true if everything has gone as planned, return false if the new folder could not be created (rights etc?)
	return true;
}

function attach_generate_pathname($storagepath = '')
{
	global $lang_attach;
	if(strlen($storagepath) != 0)
	{
		$not_unique=true;

		while($not_unique)
		{
			$newdir = attach_generate_pathname();

			if(!is_dir($storagepath.$newdir))
				return $newdir;
		}
	}
	else
		return substr(md5(time().$lang_attach['Put salt'].rand(0, 1E6)),0,32);
}

function attach_generate_filename($storagepath, $messagelenght = 0, $filesize = 0)
{
	global $lang_attach;
	$not_unique=true;
	while($not_unique)
	{
		$newfile = md5(attach_generate_pathname().$messagelenght.$filesize.$lang_attach['Some more salt keywords']).'.attach';
		if(!is_file($storagepath.$newfile))return $newfile;
	}
}

?>
