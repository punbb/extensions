<?php

/**
 * pun_bbcode bar with buttons and smilies
 *
 * @copyright (C) 2008-2009 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_bbcode
 */

if (!defined('FORUM'))
	die();

// NOTE: I couldn't find how to remove sf-set from here.
?>
	<div class="sf-set" id="pun_bbcode_bar">
		<div id="pun_bbcode_wrapper"<?php echo $forum_user['pun_bbcode_use_buttons']?' class="graphical"':'' ?>>
			<div id="pun_bbcode_buttons">
<?php

// List of tags, which may have attribute
$tags_without_attr = array('b', 'i', 'u', 'url', 'email', 'img', 'list', 'li' => '*', 'quote', 'code');

// List of tags, which may not to have attribute
if ($forum_user['pun_bbcode_use_buttons'])
	$tags_with_attr = array('color');
else
	$tags_with_attr = array('quote', 'color', 'url', 'email', 'img', 'list');

// Let's get the list of all tags
$tags = array_unique(array_merge($tags_without_attr, $tags_with_attr));

if ($forum_user['pun_bbcode_use_buttons'])
{
	if (file_exists($ext_info['path'].'/buttons/'.$forum_user['style'].'/'))
		$buttons_path = $ext_info['url'].'/buttons/'.$forum_user['style'];
	else
		$buttons_path = $ext_info['url'].'/buttons/Oxygen';
}
$pun_bbcode_tabindex = 1;

foreach ($tags as $filename => $tag)
{
	if (in_array($tag, $tags_without_attr))
	{
		if ($forum_user['pun_bbcode_use_buttons'])
			echo '<img src="'.$buttons_path.'/'.(is_numeric($filename)?$tag:$filename).'.png" alt="['.$tag.']" title="'.$tag.'"';
		else
			echo '<input type="button" value="'.ucfirst($tag).'" name="'.$tag.'"';

		echo ' onclick="insert_text(\'['.$tag.']\',\'[/'.$tag.']\')" pun_bbcode_tabindex="'.$pun_bbcode_tabindex.'"/>';
	}

	if (in_array($tag, $tags_with_attr))
	{
		if ($forum_user['pun_bbcode_use_buttons'])
			echo '<img src="'.$buttons_path.'/'.(is_numeric($filename)?$tag:$filename).'.png" alt="['.$tag.'=]" title="'.$tag.'="';
		else
			echo '<input type="button" value="'.ucfirst($tag).'=" name="'.$tag.'"';

		echo ' onclick="insert_text(\'['.$tag.'=]\',\'[/'.$tag.']\')" pun_bbcode_tabindex="'.$pun_bbcode_tabindex.'" />';
	}

	$pun_bbcode_tabindex++;
}

?>
			</div>
			<div id="pun_bbcode_smilies">
<?php

// Display the smiley set
foreach (array_unique($smilies) as $smile_text => $smile_file)
	echo '<img src="'.$base_url.'/img/smilies/'.$smile_file.'" width="15" height="15" alt="'.$smile_text.'" onclick="insert_text(\''.$smile_text.'\', \'\');" pun_bbcode_tabindex="'.($pun_bbcode_tabindex++).'" />'."\n";

?>			</div>
		</div>
	</div>
