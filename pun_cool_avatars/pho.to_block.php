<?php

/**
 * pun_cool_avatars block in user profile
 *
 * @copyright (C) 2009 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_cool_avatars
 */
 
 ?>
<div class="main-content main-frm">
    <form class="frm-form" method="post" accept-charset="utf-8" action="#" enctype="multipart/form-data">
		<div class="hidden">
			<?php echo implode("\n\t\t\t\t", $forum_page['hidden_fields'])."\n" ?>
		</div>
        <?php

			$forum_page['item_count'] = 0;
			if (!$pho_to_templates['AET']['error'])
			{
				foreach ($pho_to_templates['AET']['templates'] as $group => $template_list) 
				{

				?>
                <div class="content-head">
					<h2 class="hn">
						<span><?php echo $group ?></span>
					</h2>
				</div>
                <fieldset class="frm-group group1">
					<?php 

					foreach ($template_list as $template)
					{ 

					?>
					<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
						<div class="sf-box checkbox">
							<span class="fld-input">
								<input id="fld<?php echo $forum_page['item_count'] ?>" type="checkbox" value="1" name="methods[<?php echo $template ?>]"/>
							</span>
							<label for="fld<?php echo $forum_page['item_count'] ?>">
								<span><?php echo $template ?></span>
							</label>
						</div>
					</div>
                    <?php

                    }
					$forum_page['group_count']++;

					?>
				</fieldset>
                <?php

				}
			}

		?>
        
	</form>
</div>