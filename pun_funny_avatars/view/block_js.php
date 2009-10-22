<?php

/**
 * pun_funny_avatars javascript block
 *
 * @copyright (C) 2009 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_funny_avatars
 */

if (!$funny_templates['FET']['error']): ?>
<script type="text/javascript">
	var head_notice = document.createElement('h3');
	head_notice.className = 'hn ct-legend';
	head_notice.innerHTML = '<?php echo $lang_pun_funny_avatars['Template preview'] ?>';

	function updateFETPreview()
	{
		var images = new Array(<?php echo $forum_page['preview_fet_templates_str']; ?>);
		var fet_template = document.getElementById('fet_template');

		document.getElementById('fet_template_image').src = images[fet_template.selectedIndex];
	}
	var fet_div = document.getElementById('div_fet');;
	Forum.addEvent(document.getElementById('fet_template'), 'change', updateFETPreview, false);
	var imgElemFet = document.createElement('img');
	imgElemFet.id = 'fet_template_image';

	fet_div.appendChild(head_notice);
	fet_div.appendChild(imgElemFet);
	updateFETPreview();
</script>
<?php endif; ?>