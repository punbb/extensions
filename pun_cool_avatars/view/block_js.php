<?php

/**
 * pun_cool_avatars javascript block
 *
 * @copyright (C) 2009 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_cool_avatars
 */

?>
<script type="text/javascript">
	function updateImg()
	{
		var images = new Array(<?php echo $forum_page['preview_templates_str']; ?>);
		var aet_template = document.getElementById('aet_template');

		document.getElementById('template_image').src = images[aet_template.selectedIndex];
	}
	Forum.addEvent(document.getElementById('aet_template'), 'change', updateImg, false);
	Forum.addPreviewTemplatesBlock();
</script>