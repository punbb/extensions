<?php

/**
 * Page with registered events
 *
 * @copyright (C) 2008-2009 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_admin_events
 */

$event_page = (isset($_GET['p'])) ? intval($_GET['p']) : 1;

require $ext_info['path'].'/lang/'.$forum_user['language'].'/'.$ext_info['id'].'.php';
require $ext_info['path'].'/functions.php';

// Setup breadcrumbs
$forum_page['crumbs'] = array(
	array($forum_config['o_board_title'], forum_link($forum_url['index'])),
	array($lang_admin_common['Forum administration'], forum_link($forum_url['admin_index'])),
	$lang_pun_admin_events['Events']
);

$arr_min = array();
$arr_max = array();

$query = array(
	'SELECT'	=> 'MIN(date), MAX(date)',
	'FROM'		=> 'pun_admin_events'
);

$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

$ev_nums = $forum_db->num_rows($result);

if ($ev_nums)
{
	$row = $forum_db->fetch_assoc($result);
	$min_date = $row['MIN(date)'];
	$max_date = $row['MAX(date)'];
	$real_min_date = date('Y/n/j', strtotime($min_date));
	$real_max_date = date('Y/n/j', strtotime($max_date));

	$arr_min = explode('/', $real_min_date);
	$arr_max = explode('/', $real_max_date);
	if ($arr_min == $arr_max)
		$arr_max[2] += 01;
}

define('FORUM_PAGE_SECTION', 'management');
define('FORUM_PAGE', 'admin-management-events');
require FORUM_ROOT.'header.php';

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
	<form class="frm-form" name="mainform" method="post" accept-charset="utf-8" action="<?php echo forum_link($forum_url['admin_management_events']); ?>">
		<div class="hidden">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token(forum_link($forum_url['admin_management_events'])) ?>" />
			<input type="hidden" name="page" value="1" />
		</div>
		<div class="content-head">
			<h2 class="hn">
				<span><strong><?php echo $lang_pun_admin_events['Filter selection'] ?></strong></span>
			</h2>
		</div>
		<fieldset class="frm-group group1">
			<div class="sf-set set1">
				<div class="sf-box select">
					<label for="fld-day-from">
						<span>By date: From</span>
					</label>
					<span class="fld-input">
						<?php generate_dropdown_list('fld-day-from', 'day_from', 1, 31, $arr_min[2]); ?>
						<?php generate_dropdown_list('fld-month-from', 'month_from', 1, 12, $arr_min[1]); ?>
						<?php generate_dropdown_list('fld-year-from', 'year_from', 1990, 2020, $arr_min[0]); ?>
					</span>
				</div>
				<div class="sf-box select">
					<label for="fld-day-from">
						<span>To</span>
					</label>
					<span class="fld-input">
						<?php generate_dropdown_list('fld-day-to', 'day_to', 1, 31, $arr_max[2]); ?>
						<?php generate_dropdown_list('fld-month-to', 'month_to', 1, 12, $arr_max[1]); ?>
						<?php generate_dropdown_list('fld-year-to', 'year_to', 1990, 2020, $arr_max[0]); ?>
					</span>
				</div>
			</div>
			<div class="sf-set set2">
				<div class="sf-box select">
					<label for="fld-day-from">
						<span>By type:</span>
					</label>
					<span class="fld-input">
						<select id="fld-event-id" name="event_id">
						<?php

							$query = array(
								'SELECT'	=> 'distinct type',
								'FROM'		=> 'pun_admin_events',
							);

							$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

							if(isset($_POST['event_id']) && $_POST['event_id'] != '' && $_POST['event_id'] != 0)
								echo '<option value="">'.$_POST['event_id'].'</option>';
							else
								echo '<option selected="selected" value="0">'.$lang_pun_admin_events['Any'].'</option>';

							while($_tmp = $forum_db->fetch_assoc($result))
							{
								if(isset($_POST['event_id']) && $_POST['event_id'] == $_tmp['type'])
									echo '<option selected="selected" value="'.$_tmp.'">'.$_tmp['type'].'</option>'; 
								else
									echo '<option value="'.$_tmp['type'].'">'.$_tmp['type'].'</option>'; 
							}

						?>
						</select>
					</span>
				</div>
			</div>
			<div class="sf-set set3">
				<div class="sf-box text">
					<label for="fld-ip">
						<span><?php echo $lang_pun_admin_events['By IP']; ?></span>
					</label>
					<br />
					<span class="fld-input">
						<input type="text" id="fld-ip" name="ip" value="<?php echo isset($_POST['ip']) ? $_POST['ip'] : '*'; ?>" />
					</span>
				</div>
			</div>
			<div class="sf-set set4">
				<div class="sf-box text">
					<label for="fld-ip">
						<span><?php echo $lang_pun_admin_events['By UserName']; ?></span>
					</label>
					<span class="fld-input">
						<input type="text" id="fld-ip" name="name" value="<?php echo isset($_POST['name']) ? $_POST['name'] : '*'; ?>" />
					</span>
				</div>
			</div>
			<div class="sf-set set5">
				<div class="sf-box text">
					<label for="fld-ip">
						<span><?php echo $lang_pun_admin_events['By Comment']; ?></span>
					</label>
					<span class="fld-input">
						<input id="fld-comment" name="comment" value="<?php echo isset($_POST['comment']) ? $_POST['comment'] : '*'; ?>" />
					</span>
				</div>
			</div>
			<div class="sf-set set6">
				<div class="sf-box select">
					<label for="fld-event-id">
						<span>Sort by:</span>
					</label>
					<span class="fld-input">
						<select id="fld-event-id" name="sort_by">
							<?php

							if(isset($_POST['sort_by']))
							{
								echo '<option '.(($_POST['sort_by'] == 'Date') ? 'selected="selected"' : '').' value="Date">'.$lang_pun_admin_events['Date'].'</option>';
								echo '<option '.(($_POST['sort_by'] == 'Type') ? 'selected="selected"' : '').' value="Type">'.$lang_pun_admin_events['Event'].'</option>'; 
								echo '<option '.(($_POST['sort_by'] == 'IP') ? 'selected="selected"' : '').'value="IP">'.$lang_pun_admin_events['IP'].'</option>'; 
								echo '<option '.(($_POST['sort_by'] == 'user_name') ? 'selected="selected"' : '').' value="user_name">'.$lang_pun_admin_events['User_Name'].'</option>';
							}
							else
							{
								echo '<option selected="selected" value="Date">'.$lang_pun_admin_events['Date'].'</option>';
								echo '<option value="Type">'.$lang_pun_admin_events['Event'].'</option>'; 
								echo '<option value="IP">'.$lang_pun_admin_events['IP'].'</option>'; 
								echo '<option value="user_name">'.$lang_pun_admin_events['User_Name'].'</option>'; 
							}

							?>
						</select>
						<select id="fld-event-id" name="sort_rule">
							<?php

							if(isset($_POST['sort_rule']) && $_POST['sort_rule'] == 'DESC')
							{
								echo '<option value="ASC">'.$lang_pun_admin_events['ASC'].'</option>';
								echo '<option selected="selected" value="DESC">'.$lang_pun_admin_events['DESC'].'</option>'; 
							}
							else
							{
								echo '<option selected="selected" value="ASC">'. $lang_pun_admin_events['ASC'].'</option>';
								echo '<option value="DESC">'.$lang_pun_admin_events['DESC'].'</option>'; 
							}

							?>
						</select>
					</span>

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

		//$results_onpage - results per page
		$results_onpage = 10;

		//Prepare ORDER clause.
		$order_by = '';

		if(isset($_POST['sort_rule']))
		{
			if(isset($_POST['sort_by']))
				$order_by = $_POST['sort_by'].' '.$_POST['sort_rule'];
			else
				$order_by = 'date'.' '.$_POST['sort_rule'];
		}

		//Prepare WHERE clause
		$sql_where = implode(' && ', pun_events_generate_where());

		//Count rows
		$query = array(
			'SELECT'	=> 'count(*)',
			'FROM'		=> 'pun_admin_events',
			'WHERE'		=> $sql_where,
		);
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		$num_rows = $forum_db->result($result);
		$data = array();

		//if no rows was founded don't try to find it again =)
		if($num_rows != 0)
		{
			$query = array(
				'SELECT'	=> 'ip, type, comment, date, user_name',
				'FROM'		=> 'pun_admin_events',
				'WHERE'		=> $sql_where,
				'ORDER BY'	=> $order_by,
				'LIMIT'		=> ($event_page ? ((($event_page - 1) * $results_onpage).', '.$results_onpage) : ('0, '.$results_onpage))
			);

			$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
			while($_tmp = $forum_db->fetch_assoc($result))
				$data[] = $_tmp;
		}

		//Draw results
		$schema = array(
			'ip'			=> $lang_pun_admin_events['IP'],
			'type'			=> $lang_pun_admin_events['Type'],
			'comment'		=> $lang_pun_admin_events['Comment'],
			'date'			=> $lang_pun_admin_events['Date'],
			'user_name'		=> $lang_pun_admin_events['User_Name'],
		);

		$lang = array(
			'Pages'			=> $lang_pun_admin_events['Pages'],
			'Results'		=> $lang_pun_admin_events['Results'],
			'Nothing found' => $lang_pun_admin_events['Nothing found']
		);

		pagination($schema, $data, $event_page, ceil($num_rows/$results_onpage), 'mainform', $lang);

	?>
</div>
<?php

$tpl_temp = trim(ob_get_contents());
$tpl_main = str_replace('<!-- forum_main -->', $tpl_temp, $tpl_main);

ob_end_clean();

// END SUBST - <!-- forum_main -->

require FORUM_ROOT.'footer.php';

?>