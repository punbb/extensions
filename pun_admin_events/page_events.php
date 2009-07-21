<?php

/**
 * Page with registered events
 *
 * @copyright (C) 2008-2009 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_admin_events
 */

if (file_exists($ext_info['path'].'/lang/'.$forum_user['language'].'/'.$ext_info['id'].'.php'))
	require $ext_info['path'].'/lang/'.$forum_user['language'].'/'.$ext_info['id'].'.php';
else
	require $ext_info['path'].'/lang/English/'.$ext_info['id'].'.php';
require $ext_info['path'].'/functions.php';
//Was data sent?
if (isset($_POST['sort_rule']))
	$tmp_where = pun_events_generate_where();

if (isset($_POST['p']) || isset($_GET['p']))
{
	if (isset($_POST['p']))
		$event_page = intval($_POST['p']);
	if (isset($_GET['p']))
		$event_page = intval($_GET['p']);
}
else
	$event_page = 1;

// Setup breadcrumbs
$forum_page['crumbs'] = array(
	array($forum_config['o_board_title'], forum_link($forum_url['index'])),
	array($lang_admin_common['Forum administration'], forum_link($forum_url['admin_index'])),
	$lang_pun_admin_events['Events']
);

define('FORUM_PAGE_SECTION', 'management');
define('FORUM_PAGE', 'admin-management-events');
require FORUM_ROOT.'header.php';

$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;
// START SUBST - <!-- forum_main -->
ob_start();
?>
<div class="main-subhead">
	<h3 class="hn">
		<span><?php echo $lang_pun_admin_events['Name page'] ?></span>
	</h3>
</div>
<div id="pun-main" class="main sectioned admin">
	<div class="main-content main-frm">
	<form class="frm-form" method="post" name="events_form" accept-charset="utf-8" action="<?php echo forum_link($forum_url['admin_management_events']); ?>">
		<div class="hidden">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token(forum_link($forum_url['admin_management_events'])) ?>" />
			<input type="hidden" name="p" value="1" />
		</div>
		<div class="ct-box">
			<p class="warn"><?php echo $lang_pun_admin_events['Page notice'] ?></p>
		</div>
		<div class="content-head">
			<h2 class="hn"><span><?php echo $lang_pun_admin_events['Filter selection'] ?></span></h2>
		</div>
		<fieldset class="frm-group group1">
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>">
							<span><?php echo $lang_pun_admin_events['From'] ?></span>
							<small><?php echo $lang_pun_admin_events['Date help'] ?></small>
						</label>
						<br/>
						<span class="fld-input">
							<input id="fld<?php echo $forum_page['fld_count'] ?>" value="<?php echo isset($_POST['date_from']) ? forum_htmlencode($_POST['date_from']) : ''; ?>" type="text" maxlength="10" size="15" name="date_from"/>
						</span>
					</div>
				</div>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>">
							<span><?php echo $lang_pun_admin_events['To'] ?></span>
							<small><?php echo $lang_pun_admin_events['Date help'] ?></small>
						</label>
						<br/>
						<span class="fld-input">
							<input id="fld<?php echo $forum_page['fld_count'] ?>" value="<?php echo isset($_POST['date_to']) ? forum_htmlencode($_POST['date_to']) : ''; ?>" type="text" maxlength="10" size="15" name="date_to"/>
						</span>
					</div>
				</div>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box select">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_pun_admin_events['By type'] ?></span></label><br />
						<span class="fld-input"><select id="fld<?php echo $forum_page['fld_count'] ?>" name="event_id">
						<?php

							$query = array(
								'SELECT'	=> 'DISTINCT type',
								'FROM'		=> 'pun_admin_events',
							);
							$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
							echo '<option '.((isset($_POST['event_id']) && !empty($_POST['event_id']) && $_POST['event_id'] != -1) ? 'selected="selected"' : '').' value="-1">'.$lang_pun_admin_events['Any'].'</option>';

							while ($cur_type = $forum_db->fetch_assoc($result))
							{
								if (isset($_POST['event_id']) && $_POST['event_id'] == $cur_type['type'])
									echo '<option selected="selected" value="'.$cur_type['type'].'">'.$cur_type['type'].'</option>';
								else
									echo '<option value="'.$cur_type['type'].'">'.$cur_type['type'].'</option>';
							}

						?>
						</select></span>
					</div>
				</div>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>">
							<span><?php echo $lang_pun_admin_events['By IP']; ?></span>
						</label>
						<br/>
						<span class="fld-input">
							<input id="fld<?php echo $forum_page['fld_count'] ?>" type="text" name="ip" value="<?php echo isset($_POST['ip']) ? forum_htmlencode($_POST['ip']) : ''; ?>" />
						</span>
					</div>
				</div>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>">
							<span><?php echo $lang_pun_admin_events['By UserName']; ?></span>
						</label>
						<span class="fld-input">
							<input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="name" value="<?php echo isset($_POST['name']) ? forum_htmlencode($_POST['name']) : ''; ?>" />
						</span>
					</div>
				</div>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>">
							<span><?php echo $lang_pun_admin_events['By Comment']; ?></span>
						</label>
						<span class="fld-input">
							<input id="fld<?php echo $forum_page['fld_count'] ?>" name="comment" value="<?php echo isset($_POST['comment']) ? forum_htmlencode($_POST['comment']) : ''; ?>" />
						</span>
					</div>
				</div>
			</fieldset>
			<div class="content-head">
				<h3 class="hn"><span><?php echo $lang_pun_admin_events['Sort results'] ?></span></h3>
			</div>
			<fieldset class="frm-group group1">
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box select">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_pun_admin_events['Order by']; ?></span></label><br />
						<span class="fld-input"><select id="fld<?php echo $forum_page['fld_count'] ?>" name="sort_by">
							<?php $sort_by = isset($_POST['sort_by']) && in_array($_POST['sort_by'], array('Date', 'Type', 'IP', 'user_name')) ? $_POST['sort_by'] : 'Date'; ?>
							<option value="Date" <?php echo $sort_by == 'Date' ? 'selected="selected"': ''; ?>><?php echo $lang_pun_admin_events['Date']; ?></option>
							<option value="Type" <?php echo $sort_by == 'Type' ? 'selected="selected"' : ''; ?>><?php echo $lang_pun_admin_events['Event']; ?></option>
							<option value="IP" <?php echo $sort_by == 'IP' ? 'selected="selected"' : ''; ?>><?php echo $lang_pun_admin_events['IP']; ?></option>
							<option value="user_name" <?php echo $sort_by == 'user_name' ? 'selected="selected"' : ''; ?>><?php echo $lang_pun_admin_events['User_Name']; ?></option>
						</select></span>
					</div>
				</div>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box select">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_pun_admin_events['Sort order']; ?></span></label><br />
						<span class="fld-input"><select id="fld<?php echo $forum_page['fld_count'] ?>" name="sort_rule">
							<?php $sort_rule = isset($_POST['sort_rule']) && ($_POST['sort_rule'] == 'DESC' || $_POST['sort_rule'] == 'ASC') ? $_POST['sort_rule'] : 'DESC'; ?>
							<option value="ASC" <?php echo $sort_rule == 'ASC' ? 'selected="selected"' : '';?>><?php echo $lang_pun_admin_events['ASC']; ?></option>
							<option value="DESC" <?php echo $sort_rule == 'DESC' ? 'selected="selected"' : '';?>><?php echo $lang_pun_admin_events['DESC']; ?></option>
						</select></span>
					</div>
				</div>
			</fieldset>
		<div class="frm-buttons">
			<span class="submit">
				<input type="submit" value="<?php echo $lang_pun_admin_events['Search'] ?>" />
			</span>
		</div>
	</form>
	</div>
</div>

<div class="main-content frm">
	<?php

		$results_onpage = $forum_config['o_disp_topics_default'];
		$query = array(
			'SELECT'	=> 'COUNT(*)',
			'FROM'		=> 'pun_admin_events'
		);
		if (isset($tmp_where) && !empty($tmp_where['WHERE']))
			$query['WHERE'] = $tmp_where['WHERE'];
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		list($num_rows) = $forum_db->fetch_row($result);
		
		$data = array();
		if ($num_rows > 0)
		{
			$query = array(
				'SELECT'	=> 'ip, type, comment, date, user_name',
				'FROM'		=> 'pun_admin_events',
				'LIMIT'		=> ($event_page - 1) * $results_onpage.', '.$results_onpage
			);
			if (isset($tmp_where))
			{
				if (!empty($tmp_where['WHERE']))
					$query['WHERE'] = $tmp_where['WHERE'];
				$query['ORDER BY'] = $tmp_where['ORDER BY'];
			}
	
			$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
			while($cur_event = $forum_db->fetch_assoc($result))
				$data[] = $cur_event;
		}

		//Draw results
		show_data($data, ceil($num_rows/$results_onpage));

	?>
</div>
<?php

$tpl_temp = trim(ob_get_contents());
$tpl_main = str_replace('<!-- forum_main -->', $tpl_temp, $tpl_main);

ob_end_clean();

// END SUBST - <!-- forum_main -->

require FORUM_ROOT.'footer.php';

?>