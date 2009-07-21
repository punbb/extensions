<?php

/**
 * pun_admin_events functions
 *
 * @copyright (C) 2008-2009 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_admin_events
 */

function show_data($data, $pages)
{
	global $forum_url, $results_onpage, $lang_common, $lang_pun_admin_events, $event_page;

	if (!empty($data))
	{
		$pagination = paginate($pages, $event_page, $forum_url['admin_management_events'], $lang_common['Paging separator']);
		$fields = array(
			'ip'			=> $lang_pun_admin_events['IP'],
			'type'			=> $lang_pun_admin_events['Type'],
			'comment'		=> $lang_pun_admin_events['Comment'],
			'date'			=> $lang_pun_admin_events['Date'],
			'user_name'		=> $lang_pun_admin_events['User_Name']
		);

		?>
		<div id="brd-events-pagination-top" class="main-pagepost gen-content">
			<p class="paging"><span class="pages"><?php echo $lang_common['Pages']; ?></span><?php echo $pagination; ?></p>
		</div>
		<div class="main-head">
			<p><span class="item-info"><?php echo $lang_pun_admin_events['Results']; ?></span></p>
		</div>
		<div class="ct-group">
			<table cellspacing="0" summary="Table summary">
				<thead>
					<tr>
						<?php

							$eve_iter = 0;
							foreach($fields as $key => $value)
							{
								echo '<th class="tc'.$eve_iter.'" scope="col">'.$value.'</th>';
								$eve_iter++;
							}

						?>
					</tr>
				</thead>
				<tbody>
			<?php

			$event2_iter = 1;
			foreach ($data as $row)
			{

			?>
				<?php

					if ($event2_iter == 0)
						echo '<tr class="odd row1">';
					else if (($event2_iter % 2) == 0)
						echo '<tr class="odd">';
					else
						echo '<tr class="even">';

					$event_iter = 0;

					foreach($fields as $key => $value)
					{
						echo '<td class="tc'.$event_iter.'">'.($event_iter == 3 ? format_time($row[$key]) : $row[$key]).'</td>';
						$event_iter++;
					}

				?>
				</tr>
			<?php

				$event2_iter++;
			}

			?>
				</tbody>
			</table>
		</div>

		<div class="main-foot">
			<p><span class="item-info"><?php echo $lang_pun_admin_events['Results']; ?></span></p>
		</div>
		<div id="brd-events-pagination-bottom" class="main-pagepost gen-content">
			<p class="paging"><span class="pages"><?php echo $lang_common['Pages']; ?></span><?php echo $pagination; ?></p>
		</div>
		<?php

	}
	else
	{

		?>
			<div class="ct-box">
				<p><strong><?php echo $lang_pun_admin_events['Nothing found'] ?></strong></p>
			</div>
		<?php

	}
}

function pun_events_generate_where()
{
	global $forum_db, $lang_common, $db_type, $lang_pun_admin_events;
	$result = array();
	
	$date_from = isset($_POST['date_from']) ? forum_trim($_POST['date_from']) : '';
	$date_to = isset($_POST['date_to']) ? forum_trim($_POST['date_to']) : '';
	if (!empty($date_from))
	{
		$date_from = strtotime($date_from);
		if (!$date_from)
			message($lang_pun_admin_events['Incor from']);
	}
	if (!empty($date_to))
	{
		$date_to = strtotime($date_to);
		if (!$date_to)
			message($lang_pun_admin_events['Incor to']);
	}

	if (!empty($date_from) && !empty($date_to))
	{
	 	if ($date_from > $date_to)
			message('');
		if ($date_from == $date_to)
			$date_to += 86400;
	}
	if (!empty($date_from))
		$result['WHERE'][] = 'date >= '.$date_from;
	if (!empty($date_to))
		$result['WHERE'][] = 'date <= '.$date_to;

	if (isset($_POST['event_id']) && is_scalar($_POST['event_id']))
	{
		if ($_POST['event_id'] != -1)
		{
			$query = array(
				'SELECT'	=> '1',
				'FROM'		=> 'pun_admin_events',
				'WHERE'		=> 'type = \''.$forum_db->escape($_POST['event_id']).'\''
			);
			$event_res = $forum_db->query_build($query) or error(__FILE__, __LINE__);
			if (!$forum_db->num_rows($event_res))
				message($lang_common['Bad request']);
			$event_id = $_POST['event_id'];
		}
		else
			$event_id = 0;
	}
	else
		message($lang_common['Bad request']);
	if ($event_id !== 0)
		$result['WHERE'][] = 'type = \''.$forum_db->escape($event_id).'\'';

	$sort_by = isset($_POST['sort_by']) && in_array($_POST['sort_by'], array('Date', 'Event', 'IP', 'user_name')) ? $_POST['sort_by'] : message($lang_common['Bad request']);
	$sort_rule = isset($_POST['sort_rule']) && ($_POST['sort_rule'] == 'ASC' || $_POST['sort_rule'] == 'DESC') ? $_POST['sort_rule'] : message($lang_common['Bad request']);
	$result['ORDER BY'] = $sort_by.' '.$sort_rule;

	$like_command = ($db_type == 'pgsql') ? 'ILIKE' : 'LIKE';
	if (isset($_POST['ip']) && !empty($_POST['ip'])  && $_POST['ip'] != '*')
	{
		$ip = $_POST['ip'];
		if (empty($ip) || (!preg_match('/[0-9\*]{1,3}\.[0-9\*]{1,3}\.[0-9\*]{1,3}\.[0-9\*]{1,3}/', $ip) && !preg_match('/^((([0-9A-Fa-f\*]{1,4}:){7}[0-9A-Fa-f\*]{1,4})|(([0-9A-Fa-f\*]{1,4}:){6}:[0-9A-Fa-f\*]{1,4})|(([0-9A-Fa-f\*]{1,4}:){5}:([0-9A-Fa-f\*]{1,4}:)?[0-9A-Fa-f\*]{1,4})|(([0-9A-Fa-f\*]{1,4}:){4}:([0-9A-Fa-f\*]{1,4}:){0,2}[0-9A-Fa-f\*]{1,4})|(([0-9A-Fa-f\*]{1,4}:){3}:([0-9A-Fa-f\*]{1,4}:){0,3}[0-9A-Fa-f\*]{1,4})|(([0-9A-Fa-f\*]{1,4}:){2}:([0-9A-Fa-f\*]{1,4}:){0,4}[0-9A-Fa-f\*]{1,4})|(([0-9A-Fa-f\*]{1,4}:){6}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(([0-9A-Fa-f]{1,4}:){0,5}:((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(::([0-9A-Fa-f]{1,4}:){0,5}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|([0-9A-Fa-f\*]{1,4}::([0-9A-Fa-f\*]{1,4}:){0,5}[0-9A-Fa-f\*]{1,4})|(::([0-9A-Fa-f\*]{1,4}:){0,6}[0-9A-Fa-f\*]{1,4})|(([0-9A-Fa-f\*]{1,4}:){1,7}:))$/', $ip)))
		message($lang_pun_admin_events['Invalid IP']);
		if (strpos($ip, '*'))		
			$result['WHERE'][] = 'ip '.$like_command.' \''.$forum_db->escape(str_replace('*', '%', $ip)).'\'';
		else
			$result['WHERE'][] = 'ip = \''.$forum_db->escape(str_replace('*', '%', $ip)).'\'';
	}

	if (isset($_POST['name']) && !empty($_POST['name']) && $_POST['name'] != '*')
		$result['WHERE'][] = 'user_name '.$like_command.' \''.$forum_db->escape(str_replace('*', '%', $_POST['name'])).'\'';
	if (isset($_POST['comment']) && !empty($_POST['comment']) && $_POST['comment'] != '*')
		$result['WHERE'][] = 'comment '.$like_command.' \''.$forum_db->escape(str_replace('*', '%', $_POST['comment'])).'\'';

	return array('ORDER BY' => $result['ORDER BY'], 'WHERE' => isset($result['WHERE']) ? implode(' && ', $result['WHERE']) : '');
}

?>