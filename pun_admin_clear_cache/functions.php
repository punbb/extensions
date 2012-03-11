<?php

/***********************************************************************

	Copyright (C) 2008-2012  PunBB

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

// Remove all the files matching <FORUM_CACHE_DIR>/cache_*.php
function pun_admin_clear_cache()
{
	$count = 0; // The number of files actually removed

	$d = dir(FORUM_CACHE_DIR);

	while (($entry = $d->read()) !== false)
	{
		if (is_file(FORUM_CACHE_DIR.$entry) && is_writable(FORUM_CACHE_DIR.$entry) && (substr($entry, 0, 6)  == 'cache_') && (substr($entry, -4) == '.php'))
		{
			if (unlink(FORUM_CACHE_DIR.$entry))
				$count++;
		}
	}

	$d->close();

	return $count;
}
