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

function pun_bbcode_bar() {
	global $forum_config, $forum_user, $ext_info, $smilies, $base_url;

	ob_start();

	// NOTE: I couldn't find how to remove sf-set from here.
?>
<div id="pun_bbcode_wrapper"<?php echo $forum_user['pun_bbcode_use_buttons'] && $forum_config['p_message_bbcode'] ? ' class="graphical"' : '' ?>>
<?php

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
		$tags_with_attr = array('quote', 'color', 'url', 'email', 'img', 'list');

	if (!$forum_user['pun_bbcode_use_buttons'] && $forum_config['p_message_img_tag'])
		$tags_with_attr[] = 'img';

	($hook = get_hook('pun_bbcode_pre_tags_merge')) ? eval($hook) : null;

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

	($hook = get_hook('pun_bbcode_pre_buttons_output')) ? eval($hook) : null;

	foreach ($tags as $filename => $tag)
	{
		($hook = get_hook('pun_bbcode_buttons_output_loop_start')) ? eval($hook) : null;

		if (in_array($tag, $tags_without_attr))
		{
			if ($forum_user['pun_bbcode_use_buttons'])
				echo "\t\t".'<img src="'.$buttons_path.'/'.(is_numeric($filename) ? $tag : $filename).'.png" alt="['.$tag.']" title="'.$tag.'"';
			else
				echo "\t\t".'<input type="button" value="'.ucfirst($tag).'" name="'.$tag.'"';

			echo ' onclick="insert_text(\'['.$tag.']\',\'[/'.$tag.']\')" tabindex="'.$pun_bbcode_tabindex.'"/>'."\n";
		}

		if (in_array($tag, $tags_with_attr))
		{
			if ($forum_user['pun_bbcode_use_buttons'])
				echo "\t\t".'<img src="'.$buttons_path.'/'.(is_numeric($filename) ? $tag : $filename).'.png" alt="['.$tag.'=]" title="'.$tag.'="';
			else
				echo "\t\t".'<input type="button" value="'.ucfirst($tag).'=" name="'.$tag.'"';

			echo ' onclick="insert_text(\'['.$tag.'=]\',\'[/'.$tag.']\')" tabindex="'.$pun_bbcode_tabindex.'" />'."\n";
		}

		$pun_bbcode_tabindex++;
	}

?>
	</div>
<?php

	}

	if ($forum_config['o_smilies'])
	{

?>

	<div id="pun_bbcode_smilies">
<?php

		($hook = get_hook('pun_bbcode_pre_smilies_output')) ? eval($hook) : null;


		if (!$forum_config['p_message_bbcode'])
			$pun_bbcode_tabindex = 1;

		// Display the smiley set
		foreach (array_unique($smilies) as $smile_text => $smile_file)
		{
			($hook = get_hook('pun_bbcode_smilies_output_loop_start')) ? eval($hook) : null;

			echo "\t\t".'<img src="'.$base_url.'/img/smilies/'.$smile_file.'" width="15" height="15" alt="'.$smile_text.'" onclick="insert_text(\' '.$smile_text.' \', \'\');" tabindex="'.($pun_bbcode_tabindex++).'" />'."\n";
		}


?>
	</div>
<?php

	}

?>
</div>
<?php

	$bbar_text = ob_get_contents();
	$bbar_text = str_replace(array('"', "\n"), array('\"', "\\\n"), $bbar_text);

	ob_end_clean();

	return $bbar_text;
}

