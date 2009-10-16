<?php

/**
 * pun_animated_avatars javascript block
 *
 * @copyright (C) 2009 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_animated_avatars
 */

if (!$animated_templates['AET']['error']): ?>
<script type="text/javascript">
	var head_notice = document.createElement('h3');
	head_notice.className = 'hn ct-legend';
	head_notice.innerHTML = '<?php echo $lang_pun_animated_avatars['Template preview'] ?>';

	function updateAETPreview()
	{
		var images = new Array(<?php echo $forum_page['preview_aet_templates_str']; ?>);
		var aet_template = document.getElementById('aet_template');

		document.getElementById('aet_template_image').src = images[aet_template.selectedIndex];
	}
	var aet_div = document.getElementById('div_aet');;
	Forum.addEvent(document.getElementById('aet_template'), 'change', updateAETPreview, false);
	var imgElemAet = document.createElement('img');
	imgElemAet.id = 'aet_template_image';

	aet_div.appendChild(head_notice);
	aet_div.appendChild(imgElemAet);
	updateAETPreview();
</script>
<?php endif; ?>