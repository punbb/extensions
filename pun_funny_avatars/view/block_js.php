<?php

/**
 * pun_cool_avatars javascript block
 *
 * @copyright (C) 2009 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_cool_avatars
 */

if (!$pho_to_templates['AET']['error'] || !$pho_to_templates['FET']['error']): ?>
<script type="text/javascript">
	var head_notice = document.createElement('h3');
	head_notice.className = 'hn ct-legend';
	head_notice.innerHTML = '<?php echo $lang_pun_cool_avatars['Template preview'] ?>';

	<?php if (!$pho_to_templates['AET']['error']): ?>
	function updateAETPreview()
	{
		var images = new Array(<?php echo $forum_page['preview_aet_templates_str']; ?>);
		var aet_template = document.getElementById('aet_template');

		document.getElementById('aet_template_image').src = images[aet_template.selectedIndex];
	}
	var aet_div = document.getElementById('div_aet');
	Forum.addEvent(document.getElementById('aet_template'), 'change', updateAETPreview, false);
	var imgElemAet = document.createElement('img');
	imgElemAet.id = 'aet_template_image';

	aet_div.appendChild(head_notice.cloneNode(true));
	aet_div.appendChild(imgElemAet);
	updateAETPreview();
	<?php endif; ?>
	<?php if (!$pho_to_templates['FET']['error']): ?>
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

	fet_div.appendChild(head_notice.cloneNode(true));
	fet_div.appendChild(imgElemFet);
	updateFETPreview();
	<?php endif; ?>
</script>
<?php endif; ?>