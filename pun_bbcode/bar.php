<?php

/***********************************************************************

	Copyright (C) 2008  PunBB

	Based on Easy BBCode extension by Rickard Andersson.

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

if (!defined('FORUM'))
    die();

ob_end_clean();
ob_start();
// NOTE: I couldn't find how to remove sf-set from here.
?>
		<div id="pun_bbcode_wrapper"<?php echo (($forum_user['pun_bbcode_use_buttons']) && ($forum_config['p_message_bbcode']))?' class="graphical"':'' ?>>
<?php
		$tabindex = -1;
		
		if ($forum_config['p_message_bbcode'])
		{
?>
			<div id="pun_bbcode_buttons">
<?php

// List of tags, which may have attribute
$tags_without_attr = array('b', 'i', 'u', 'email', 'list', 'li' => '*', 'quote', 'code', 'url');

if ($forum_config['p_message_img_tag'])
	$tags_without_attr[] = 'img';

// List of tags, which may not to have attribute
if ($forum_user['pun_bbcode_use_buttons'])
    $tags_with_attr = array('color');
else
    $tags_with_attr = array('quote', 'color', 'url', 'email', 'list');

if (!$forum_user['pun_bbcode_use_buttons'])
	if ($forum_config['p_message_img_tag'])
		$tags_with_attr[] = 'img';

// Let's get the list of all tags
$tags = array_unique(array_merge($tags_without_attr, $tags_with_attr));

if ($forum_user['pun_bbcode_use_buttons'])
{
	if (file_exists($ext_info['path'].'/buttons/'.$forum_user['style'].'/'))
		$buttons_path = $ext_info['url'].'/buttons/'.$forum_user['style'];
	else
		$buttons_path = $ext_info['url'].'/buttons/Oxygen';
}


foreach ($tags as $filename => $tag)
{
	if (in_array($tag, $tags_without_attr))
	{
		if ($forum_user['pun_bbcode_use_buttons'])
			echo '<img src="'.$buttons_path.'/'.(is_numeric($filename)?$tag:$filename).'.png" alt="['.$tag.']" title="'.$tag.'"';
		else
			echo '<input type="button" value="'.ucfirst($tag).'" name="'.$tag.'"';

		echo ' onclick="insert_text(\'['.$tag.']\',\'[/'.$tag.']\')" tabindex="'.$tabindex.'"/>';
	}

	if (in_array($tag, $tags_with_attr))
	{
		if ($forum_user['pun_bbcode_use_buttons'])
			echo '<img src="'.$buttons_path.'/'.(is_numeric($filename)?$tag:$filename).'.png" alt="['.$tag.'=]" title="'.$tag.'="';
		else
			echo '<input type="button" value="'.ucfirst($tag).'=" name="'.$tag.'"';

		echo ' onclick="insert_text(\'['.$tag.'=]\',\'[/'.$tag.']\')" tabindex="'.$tabindex.'" />';
	}

	$tabindex--;
}

?>
			</div>
			<?php
		}
		?>
			<div id="pun_bbcode_smilies">
			<?php
			// Display the smiley set
			foreach (array_unique($smilies) as $smile_text => $smile_file)
				echo '<a href="javascript:insert_text(\' '.$smile_text.' \', \'\');" tabindex="'.($tabindex--).'"><img src="'.$base_url.'/img/smilies/'.$smile_file.'" width="15" height="15" alt="'.$smile_text.'" /></a>'."\n";
			?>
			</div>
		</div>
<?php
$bbar_temp = str_replace(array("\n", '"'), array('\n', '\"'), forum_trim(ob_get_contents()));
ob_end_clean();
?>