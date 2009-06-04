<?php

/**
 * pun_admin_manage_extensions_improved: page with list of extensions
 *
 * @copyright Copyright (C) 2009 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_admin_manage_extensions_improved
 */

// Generate an array of installed extensions
$inst_exts = array();
$query = array(
	'SELECT'	=> 'e.*',
	'FROM'		=> 'extensions AS e',
	'ORDER BY'	=> 'e.title'
);

($hook = get_hook('aex_qr_get_all_extensions')) ? eval($hook) : null;
$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
while ($cur_ext = $forum_db->fetch_assoc($result))
	$inst_exts[$cur_ext['id']] = $cur_ext;


// Hotfixes list
if ($section == 'hotfixes')
{
	// Setup breadcrumbs
	$forum_page['crumbs'] = array(
		array($forum_config['o_board_title'], forum_link($forum_url['index'])),
		array($lang_admin_common['Forum administration'], forum_link($forum_url['admin_index'])),
		array($lang_admin_common['Extensions'], forum_link($forum_url['admin_extensions_manage'])),
		array($lang_admin_common['Manage hotfixes'], forum_link($forum_url['admin_extensions_hotfixes']))
	);

	($hook = get_hook('aex_section_hotfixes_pre_header_load')) ? eval($hook) : null;
	
	if (!defined('FORUM_PAGE_SECTION'))
		define('FORUM_PAGE_SECTION', 'extensions');
	if (!defined('FORUM_PAGE'))
		define('FORUM_PAGE', 'admin-extensions-hotfixes');
	
	require FORUM_ROOT.'header.php';

	// START SUBST - <!-- forum_main -->
	ob_start();

	($hook = get_hook('aex_section_hotfixes_output_start')) ? eval($hook) : null;

?>

	<div class="main-subhead">
		<h2 class="hn"><span><?php echo $lang_admin_ext['Hotfixes available'] ?></span></h2>
	</div>
	<div class="main-content main-frm main-hotfixes">

<?php

	$num_exts = 0;
	$num_failed = 0;
	$forum_page['item_num'] = 1;
	$forum_page['ext_item'] = array();
	$forum_page['ext_error'] = array();

	// Loop through any available hotfixes
	if (isset($forum_updates['hotfix']))
	{
		// If there's only one hotfix, add one layer of arrays so we can foreach over it
		if (!is_array(current($forum_updates['hotfix'])))
			$forum_updates['hotfix'] = array($forum_updates['hotfix']);

		foreach ($forum_updates['hotfix'] as $hotfix)
		{
			if (!array_key_exists($hotfix['attributes']['id'], $inst_exts))
			{
				$forum_page['ext_item'][] = '<div class="ct-box info-box hotfix available">'."\n\t\t\t".'<h3 class="ct-legend hn"><span>'.forum_htmlencode($hotfix['content']).'</span></h3>'."\n\t\t\t".'<ul>'."\n\t\t\t\t".'<li><span>'.sprintf($lang_admin_ext['Extension by'], 'PunBB').'</span></li>'."\n\t\t\t\t".'<li><span>'.$lang_admin_ext['Hotfix description'].'</span></li>'."\n\t\t\t".'</ul>'."\n\t\t\t\t".'<p class="options"><span class="first-item"><a href="'.$base_url.'/admin/extensions.php?install_hotfix='.urlencode($hotfix['attributes']['id']).'">'.$lang_admin_ext['Install hotfix'].'</a></span></p>'."\n\t\t".'</div>';
				++$num_exts;
			}
		}
	}

	($hook = get_hook('aex_section_hotfixes_pre_display_ext_list')) ? eval($hook) : null;

	if ($num_exts)
		echo "\t\t".implode("\n\t\t", $forum_page['ext_item'])."\n";
	else
	{

?>
		<div class="ct-box info-box">
			<p><?php echo $lang_admin_ext['No available hotfixes'] ?></p>
		</div>
<?php

	}

?>
	</div>
<?php

	($hook = get_hook('aex_section_hotfixes_output_start')) ? eval($hook) : null;

?>
	<div class="main-subhead">
		<h2 class="hn"><span><?php echo $lang_admin_ext['Installed hotfixes'] ?></span></h2>
	</div>
	<div class="main-content main-frm main-hotfixes">
<?php

	$installed_count = 0;
	foreach ($inst_exts as $id => $ext)
	{
		if (strpos($id, 'hotfix_') !== 0)
			continue;

		$forum_page['ext_actions'] = array(
			'flip'		=> '<span class="first-item"><a href="'.$base_url.'/admin/extensions.php?section=hotfixes&amp;flip='.$id.'&amp;csrf_token='.generate_form_token('flip'.$id).'">'.($ext['disabled'] != '1' ? $lang_admin_ext['Disable'] : $lang_admin_ext['Enable']).'</a></span>',
			'uninstall'	=> '<span><a href="'.$base_url.'/admin/extensions.php?section=hotfixese&amp;uninstall='.$id.'">'.$lang_admin_ext['Uninstall'].'</a></span>'
		);

		($hook = get_hook('aex_section_hotfixes_pre_ext_actions')) ? eval($hook) : null;

?>
		<div class="ct-box info-box hotfix <?php echo $ext['disabled'] == '1' ? 'disabled' : 'enabled' ?>">
			<h3 class="ct-legend hn"><span><?php echo forum_htmlencode($ext['title']) ?><?php if ($ext['disabled'] == '1') echo ' ( <span>'.$lang_admin_ext['Extension disabled'].'</span> )' ?></span></h3>
			<ul class="data-list">
				<li><span><?php printf($lang_admin_ext['Extension by'], forum_htmlencode($ext['author'])) ?></span></li>
				<li><span><?php echo ((strpos($id, 'hotfix_') !== 0) ? sprintf($lang_admin_ext['Version'], $ext['version']) : $lang_admin_ext['Hotfix']) ?></span></li>
<?php if ($ext['description'] != ''): ?>				<li><span><?php echo forum_htmlencode($ext['description']) ?></span></li>
<?php endif; ?>			</ul>
			<p class="options"><?php echo implode(' ', $forum_page['ext_actions']) ?></p>
		</div>
<?php
		$installed_count++;
	}

	if ($installed_count == 0)
	{

?>
		<div class="ct-box info-box">
			<p><?php echo $lang_admin_ext['No installed hotfixes'] ?></p>
		</div>
<?php

	}

?>
	</div>
<?php

	($hook = get_hook('aex_section_hotfixes_end')) ? eval($hook) : null;

	$tpl_temp = forum_trim(ob_get_contents());
	$tpl_main = str_replace('<!-- forum_main -->', $tpl_temp, $tpl_main);
	ob_end_clean();
	// END SUBST - <!-- forum_main -->

	require FORUM_ROOT.'footer.php';
}
// Extensions list
else
{
	if ($forum_config['o_check_for_versions'] == 1)
	{
		// Check for the new versions of the extensions istalled
		$repository_urls = array('http://punbb.informer.com/extensions');
		($hook = get_hook('aex_add_extensions_repository')) ? eval($hook) : null;

		$repository_url_by_extension = array();
		foreach (array_keys($inst_exts) as $id)
			($hook = get_hook('aex_add_repository_for_'.$id)) ? eval($hook) : null;

		if (is_readable(FORUM_CACHE_DIR.'cache_ext_version_notifications.php'))
			include FORUM_CACHE_DIR.'cache_ext_version_notifications.php';

		//Get latest timestamp in cache
		if (isset($forum_ext_repos))
		{
			$min_timestamp = 10000000000;
			foreach ( $forum_ext_repos as $rep)
				$min_timestamp = min($min_timestamp, $rep['timestamp']);
		}

		$update_hour = (isset($forum_ext_versions_update_cache) && (time() - $forum_ext_versions_update_cache > 60 * 60));

		// Update last versions if there is no cahe or some extension was added/removed or one day has gone since last update
		$update_new_versions_cache = !defined('FORUM_EXT_VERSIONS_LOADED') || (isset($forum_ext_last_versions) && array_diff($inst_exts, $forum_ext_last_versions) != array()) || $update_hour  || ( $update_hour && isset($min_timestamp) && (time() - $min_timestamp > 60*60*24));

		($hook = get_hook('aex_before_update_checking')) ? eval($hook) : null;

		if ($update_new_versions_cache)
		{
			if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
				require_once FORUM_ROOT.'include/cache.php';

			generate_ext_versions_cache($inst_exts, $repository_urls, $repository_url_by_extension);
			include FORUM_CACHE_DIR.'cache_ext_version_notifications.php';
		}
	}

	// Setup breadcrumbs
	$forum_page['crumbs'] = array(
		array($forum_config['o_board_title'], forum_link($forum_url['index'])),
		array($lang_admin_common['Forum administration'], forum_link($forum_url['admin_index'])),
		array($lang_admin_common['Extensions'], forum_link($forum_url['admin_extensions_manage'])),
		array($lang_admin_common['Manage extensions'], forum_link($forum_url['admin_extensions_manage']))
	);

	($hook = get_hook('aex_section_manage_pre_header_load')) ? eval($hook) : null;

	if (!defined('FORUM_PAGE_SECTION'))
		define('FORUM_PAGE_SECTION', 'extensions');
	if (!defined('FORUM_PAGE'))
		define('FORUM_PAGE', 'admin-extensions-manage');
	require FORUM_ROOT.'header.php';

	// START SUBST - <!-- forum_main -->
	ob_start();

	($hook = get_hook('aex_section_install_output_start')) ? eval($hook) : null;

?>
	<div class="main-subhead">
		<h2 class="hn"><span><?php echo $lang_admin_ext['Extensions available'] ?></span></h2>
	</div>
	<div class="main-content main-frm main-extensions">

<?php

	$num_exts = 0;
	$num_failed = 0;
	$forum_page['item_num'] = 1;
	$forum_page['ext_item'] = array();
	$forum_page['ext_error'] = array();

	$d = dir(FORUM_ROOT.'extensions');
	while (($entry = $d->read()) !== false)
	{
		if ($entry{0} != '.' && is_dir(FORUM_ROOT.'extensions/'.$entry))
		{
			if (preg_match('/[^0-9a-z_]/', $entry))
			{
				$forum_page['ext_error'][] = '<div class="ext-error databox db'.++$forum_page['item_num'].'">'."\n\t\t\t\t".'<h3 class="legend"><span>'.sprintf($lang_admin_ext['Extension loading error'], forum_htmlencode($entry)).'</span></h3>'."\n\t\t\t\t".'<p>'.$lang_admin_ext['Illegal ID'].'</p>'."\n\t\t\t".'</div>';
				++$num_failed;
				continue;
			}
			else if (!file_exists(FORUM_ROOT.'extensions/'.$entry.'/manifest.xml'))
			{
				$forum_page['ext_error'][] = '<div class="ext-error databox db'.++$forum_page['item_num'].'">'."\n\t\t\t\t".'<h3 class="legend"><span>'.sprintf($lang_admin_ext['Extension loading error'], forum_htmlencode($entry)).'<span></h3>'."\n\t\t\t\t".'<p>'.$lang_admin_ext['Missing manifest'].'</p>'."\n\t\t\t".'</div>';
				++$num_failed;
				continue;
			}

			// Parse manifest.xml into an array
			$ext_data = is_readable(FORUM_ROOT.'extensions/'.$entry.'/manifest.xml') ? xml_to_array(file_get_contents(FORUM_ROOT.'extensions/'.$entry.'/manifest.xml')) : '';
			if (empty($ext_data))
			{
				$forum_page['ext_error'][] = '<div class="ext-error databox db'.++$forum_page['item_num'].'">'."\n\t\t\t\t".'<h3 class="legend"><span>'.sprintf($lang_admin_ext['Extension loading error'], forum_htmlencode($entry)).'<span></h3>'."\n\t\t\t\t".'<p>'.$lang_admin_ext['Failed parse manifest'].'</p>'."\n\t\t\t".'</div>';
				++$num_failed;
				continue;
			}

			// Validate manifest
			$errors = validate_manifest($ext_data, $entry);
			if (!empty($errors))
			{
				$forum_page['ext_error'][] = '<div class="ext-error databox db'.++$forum_page['item_num'].'">'."\n\t\t\t\t".'<h3 class="legend"><span>'.sprintf($lang_admin_ext['Extension loading error'], forum_htmlencode($entry)).'</span></h3>'."\n\t\t\t\t".'<p>'.implode(' ', $errors).'</p>'."\n\t\t\t".'</div>';
				++$num_failed;
			}
			else
			{
				if (!array_key_exists($entry, $inst_exts) || (version_compare($inst_exts[$entry]['version'], $ext_data['extension']['version'], '!=') && array_key_exists($entry, $inst_exts)))
				{
					$forum_page['ext_item'][] = '
						<div class="ct-box info-box extension available">
							'."\n\t\t\t".'
								<h3 class="ct-legend hn">
									<span>
										'.forum_htmlencode($ext_data['extension']['title']).'
									</span>
								</h3>
							'."\n\t\t\t".'
								<ul class="data-list">
									'."\n\t\t\t\t".'
										<li>
											<span>
												'.sprintf($lang_admin_ext['Extension by'], forum_htmlencode($ext_data['extension']['author'])).'
											</span>
										</li>
									'."\n\t\t\t\t".'
										<li>
											<span>
												'.sprintf($lang_admin_ext['Version'], $ext_data['extension']['version']).'
											</span>
										</li>
									'.(($ext_data['extension']['description'] != '') ? "\n\t\t\t\t".'<li><span>'.forum_htmlencode($ext_data['extension']['description']).'</span></li>' : '')."
									\n\t\t\t".'
								</ul>
							'."\n\t\t\t".'
							<p class="options">
								<span class="first-item">
									<a href="'.$base_url.'/admin/extensions.php?install='.urlencode($entry).'">'.(isset($inst_exts[$entry]['version']) ? $lang_admin_ext['Upgrade extension'] : $lang_admin_ext['Install extension']).'</a>
								</span>
							</p>
							'."\n\t\t".'
						</div>';
					++$num_exts;
				}
			}
		}
	}
	$d->close();

	($hook = get_hook('aex_section_install_pre_display_available_ext_list')) ? eval($hook) : null;

	if ($num_exts)
		echo "\t\t".implode("\n\t\t", $forum_page['ext_item'])."\n";
	else
	{

?>
		<div class="ct-box info-box">
			<p><?php echo $lang_admin_ext['No available extensions'] ?></p>
		</div>
<?php

	}

	// If any of the extensions had errors
	if ($num_failed)
	{

?>
		<div class="ct-box data-box">
			<p class="important"><?php echo $lang_admin_ext['Invalid extensions'] ?></p>
			<?php echo implode("\n\t\t\t", $forum_page['ext_error'])."\n" ?>
		</div>
<?php

	}
	
	if (!$num_exts)
		echo '</div>';

	($hook = get_hook('aex_section_manage_output_start')) ? eval($hook) : null;

?>
	</div>
	
	<div class="main-subhead" id="ext_instal">
		<h2 class="hn"><span><?php echo $lang_admin_ext['Installed extensions'] ?></span></h2>
	</div>
	<div class="main-content main-frm main-extensions" id="ext_instal">
		<form method="post" accept-charset="utf-8" action="<?php echo $base_url ?>/admin/extensions.php?section=manage&amp;multy">
	
<?php

	$installed_count = 0;
	$forum_page['ext_item'] = array();
	foreach ($inst_exts as $id => $ext)
	{
		if (strpos($id, 'hotfix_') === 0)
			continue;

		$forum_page['ext_actions'] = array(
			'flip'		=> '<span class="first-item"><a href="'.$base_url.'/admin/extensions.php?section=manage&amp;flip='.$id.'&amp;csrf_token='.generate_form_token('flip'.$id).'">'.($ext['disabled'] != '1' ? $lang_admin_ext['Disable'] : $lang_admin_ext['Enable']).'</a></span>',
			'uninstall'	=> '<span><a href="'.$base_url.'/admin/extensions.php?section=manage&amp;uninstall='.$id.'">'.$lang_admin_ext['Uninstall'].'</a></span>'
		);

		if ($forum_config['o_check_for_versions'] == 1 && isset($forum_ext_last_versions[$id]) && version_compare($ext['version'], $forum_ext_last_versions[$id]['version'], '<'))
			$forum_page['ext_actions']['latest_ver'] = '<span><a href="'.$forum_ext_last_versions[$id]['repo_url'].'/'.$id.'/'.$id.'.zip">'.$lang_admin_ext['Download latest version'].'</a></span>';

		($hook = get_hook('aex_section_manage_pre_ext_actions')) ? eval($hook) : null;

		if ($ext['disabled'] == '1')
			$forum_page['ext_item'][] = '<div class="ct-box info-box extension disabled">'."\n\t\t".'<h3 class="ct-legend hn"><span>'.forum_htmlencode($ext['title']).' ( <span>'.$lang_admin_ext['Extension disabled'].'</span> )</span></h3>'."\n\t\t".'<p style="float: right"><label for="fld'.++$forum_page['fld_count'].'><span class="fld-label">'.$lang_pun_man_ext_improved['Select extension'].'</span><input type="checkbox" id="fld'.$forum_page['fld_count'].'" name="extens['.$id.']" value="1" '.((in_array($id, $sel_extens))?('checked = "checked"'):('')).' /></label></p><ul class="data-list">'."\n\t\t\t".'<li><span>'.sprintf($lang_admin_ext['Extension by'], forum_htmlencode($ext['author'])).'</span></li>'."\n\t\t\t".'<li><span>'.sprintf($lang_admin_ext['Version'], $ext['version']).'</span></li>'."\n\t\t\t".(($ext['description'] != '') ? '<li><span>'.forum_htmlencode($ext['description']).'</span></li>' : '')."\n\t\t\t".'</ul>'."\n\t\t".'<p class="options">'.implode(' ', $forum_page['ext_actions']).'</p>'."\n\t".'</div>';
		else
			$forum_page['ext_item'][] = '<div class="ct-box info-box extension enabled">'."\n\t\t".'<h3 class="ct-legend hn"><span>'.forum_htmlencode($ext['title']).'</span></h3>'."\n\t\t".'<p style="float: right"><label for="fld'.++$forum_page['fld_count'].'"><span class="fld-label">'.$lang_pun_man_ext_improved['Select extension'].'</span><input type="checkbox" id="fld'.$forum_page['fld_count'].'" name="extens['.$id.']" value="1" '.((in_array($id, $sel_extens))?('checked = "checked"'):('')).' /></label></p><ul class="data-list">'."\n\t\t\t".'<li><span>'.sprintf($lang_admin_ext['Extension by'], forum_htmlencode($ext['author'])).'</span></li>'."\n\t\t\t".'<li><span>'.sprintf($lang_admin_ext['Version'], $ext['version']).'</span></li>'."\n\t\t\t".(($ext['description'] != '') ? '<li><span>'.forum_htmlencode($ext['description']).'</span></li>' : '')."\n\t\t".'</ul>'."\n\t\t".'<p class="options">'.implode(' ', $forum_page['ext_actions']).'</p>'."\n\t".'</div>';

		$installed_count++;
	}

	if ($installed_count > 0)
	{

?>

		<div class="ct-box warn-box">
			<p class="warn"><?php echo $lang_admin_ext['Installed extensions warn'] ?></p>
		</div>

<?php

		if (isset($no_selected_extensions) && $no_selected_extensions)
		{
			$display_group_buttons = true;
			echo '<div class="ct-box warn-box"><p class="warn">'.$lang_pun_man_ext_improved['No selected'].'</p></div>';
		}
		if (!empty($error_list))
		{

?>

			<div class="ct-box warn-box">
				<p class="warn"><?php echo $lang_pun_man_ext_improved['Warnings']; ?></p>
				<ul>
<?php

					foreach ($error_list as $ext => $ext_list)
						echo '<li class="warn"><span> '.sprintf($lang_pun_man_ext_improved['Work dependencies'], $ext, substr($ext_list, 0, -2)).'</span></li>';

?>

				</ul>
			</div>

<?php

		}

		echo "\t".implode("\n\t", $forum_page['ext_item'])."\n";
	}
	else
	{

?>
		<div class="ct-box info-box">
			<p><?php echo $lang_admin_ext['No installed extensions'] ?></p>
		</div>
<?php

	}
	if (isset($display_group_buttons) && $display_group_buttons && !empty($inst_exts))
	{

?>

	</div>
	<div class="main-subhead">
		<h2 class="hn"><span><?php echo $lang_pun_man_ext_improved['Extension title'] ?></span></h2>
	</div>
	<div class="main-content main-frm main-extensions">
		<div class="ct-box info-box">
			<p><?php echo $lang_pun_man_ext_improved['Ext note'] ?></p>
		</div>
		<div class="hidden">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token($base_url.'/admin/extensions.php?section=manage&amp;multy') ?>"/>
		</div>
		<div class="frm-buttons">
			<span class="submit"><input type="submit" name="disable_selected" value="<?php echo $lang_pun_man_ext_improved['Button disable'] ?>" /></span>
			<span class="submit"><input type="submit" name="enable_selected" value="<?php echo $lang_pun_man_ext_improved['Button enable'] ?>" /></span>
			<span class="submit"><input type="submit" name="uninstall_selected" value="<?php echo $lang_pun_man_ext_improved['Button uninstall'] ?>" /></span>
		</div>
	</div>
	</form>

<?php

	}

	($hook = get_hook('aex_section_manage_end')) ? eval($hook) : null;

	$tpl_temp = forum_trim(ob_get_contents());
	$tpl_main = str_replace('<!-- forum_main -->', $tpl_temp, $tpl_main);
	ob_end_clean();
	// END SUBST - <!-- forum_main -->

	require FORUM_ROOT.'footer.php';
}

?>