<?php

/**
 * pun_bbcode bar with buttons and smilies
 *
 * @copyright (C) 2008-2012 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_bbcode
 */

if (!defined('FORUM'))
	die();


class Pun_bbcode {
	private $buttons;


	//
	public function __construct() {
		$this->buttons = array();
	}


	//
	public function add_button($button = NULL) {
		if (is_null($button) || !is_array($button))
		{
			return false;
		}

		// Default button
		$default_button = array(
			//
			'name'		=> array(
				'default'	=> null,
			),

			// Default as name
			'title'		=> array(
				'default'	=> null,
			),

			// Default as name
			'tag'		=> array(
				'default'	=> null,
			),

			// without_attr, with_attr
			'type'		=> array(
				'default'	=> 'without_attr',
			),

			// default weight is 100
			'weight'		=> array(
				'default'	=> 100,
			),

			//
			'onclick'		=> array(
				'default'	=> null,
			),

			// boolean
			'image'		=> array(
				'default'	=> false,
			),

			//
			'group'		=> array(
				'default'	=> 'default',
			)
		);

		$length = count($default_button);
		$keys = array_keys($default_button);

		for ($i = 0; $i < $length; $i++)
		{
			$key = $keys[$i];
			if (!isset($button[$key]))
			{
				$default_button[$keys[$i]] = $default_button[$keys[$i]]['default'];
				continue;
			}

			$default_button[$keys[$i]] = $button[$key];
		}

		// Do not add button without name
		if (is_null($default_button['name'])) {
			return false;
		}

		// Title
		if (is_null($default_button['title'])) {
			$default_button['title'] = forum_trim($default_button['name']);
		}

		// Tag
		if (is_null($default_button['tag'])) {
			$default_button['tag'] = forum_trim($default_button['name']);
		}

		// Tweak weight
		$default_button['weight'] += count($this->buttons) / 1000;

		$this->buttons[$default_button['name']] = $default_button;

		return $this->buttons;
	}


	public function render() {
		global $forum_config, $forum_user, $ext_info, $smilies, $base_url;

		ob_start();
?>
		<div class="sf-set" id="pun_bbcode_bar">
			<div id="pun_bbcode_wrapper"<?php echo $forum_user['pun_bbcode_use_buttons'] && $forum_config['p_message_bbcode'] ? ' class="graphical"' : '' ?>>
<?php
		if ($forum_config['p_message_bbcode'])
		{
?>
			<div id="pun_bbcode_buttons">
<?php
			$this->add_button(array('name'	=> 'b', 'group' => 'text-decoration', 'weight' => 30, 'image' => true));
			$this->add_button(array('name'	=> 'i', 'group' => 'text-decoration', 'weight' => 32, 'image' => true));
			$this->add_button(array('name'	=> 'u', 'group' => 'text-decoration', 'weight' => 34, 'image' => true));

			$this->add_button(array('name'	=> 'list', 'group' => 'lists', 'weight' => 40, 'image' => true));
			$this->add_button(array('name'	=> 'list item', 'title' => '*', 'tag' => '*', 'group' => 'lists', 'weight' => 42, 'image' => true));

			$this->add_button(array('name'	=> 'quote', 'weight' => 50, 'image' => true));
			$this->add_button(array('name'	=> 'code', 'weight' => 52, 'image' => true));
			$this->add_button(array('name'	=> 'email', 'weight' => 54, 'image' => true));
			$this->add_button(array('name'	=> 'url', 'weight' => 56, 'image' => true));
			$this->add_button(array('name'	=> 'img', 'weight' => 58, 'image' => true));
			$this->add_button(array('name'	=> 'color', 'type' => 'with_attr', 'weight' => 60, 'image' => true));

			($hook = get_hook('pun_bbcode_pre_buttons_output')) ? eval($hook) : null;

			// Sort buttons
			uasort($this->buttons, array('Pun_bbcode', 'sort_buttons'));

			if (!empty($this->buttons))
			{
				$current_group = '';
				$pun_bbcode_tabindex = 1;
				foreach ($this->buttons as $name => $button)
				{
					($hook = get_hook('pun_bbcode_buttons_output_loop_start')) ? eval($hook) : null;

					// Group class
					$button_class = '';
					if ($current_group != '' && $current_group != $button['group']) {
						$button_class .= ' group_start';
					}

					// JS handler
					if (!is_null($button['onclick'])) {
						$onclick_handler = $button['onclick'];
					} else {
						if ($button['type'] == 'without_attr') {
							$onclick_handler = 'PUNBB.pun_bbcode.insert_text(\'['.$button['tag'].']\',\'[/'.$button['tag'].']\')';
						} else {
							$onclick_handler = 'PUNBB.pun_bbcode.insert_text(\'['.$button['tag'].'=]\',\'[/'.$button['tag'].']\')';
						}
					}

					// Graphical?
					$title = '';
					if ($forum_user['pun_bbcode_use_buttons'] && $button['image']) {
						$button_class .= ' image';
						$title = $button['title'];
						$button['title'] = '';
					}

					// Element ID attr can not content space — thats why we replace space in NAME with underscore
					echo '<input type="button" title="'.$title.'" class="'.$button_class.'" data-tag="'.$button['tag'].'" id="pun_bbcode_button_'.str_replace(' ', '_', $button['name']).'" value="'.$button['title'].'" name="'.$button['name'].'" onclick="'.$onclick_handler.'" tabindex="'.$pun_bbcode_tabindex.'" />';

					$pun_bbcode_tabindex++;
					$current_group = $button['group'];
				}
			}
?>
			</div>
<?php
		}
?>
		</div>
	</div>
<?php
		$bbar_text = ob_get_contents();
		ob_end_clean();

		echo $bbar_text;
	}


	// Sort libs
	private static function sort_buttons($a, $b)
	{
		// 1. Sort by weight
		if ($a['weight'] < $b['weight'])
		{
			return -1;
		}
		elseif ($a['weight'] > $b['weight'])
		{
		    return 1;
		}
		else
		{
			return 0;
		}
	}
}

?>
