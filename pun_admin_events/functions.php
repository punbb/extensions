<?php

/***********************************************************************

	Copyright (C) 2008  PunBB

	PunBB is free software; you can redistribute it and/or modify it
	under the terms of the GNU General Public License as published
	by the Free Software Foundation; either version 2 of the License,
	or (at your option) any later version.

	PunBB is distributed in the hope that it will be useful, but
	WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston,
	MA  02111-1307  USA

***********************************************************************/

function pagination($fields, $data, $page, $pages, $form_name, $lang)
{
	global $forum_url, $results_onpage;
	
	if (!empty($data))
	{
		
		?>
		
		<div class="main-pagepost gen-content">
			<span>
				<h2 class="hn"><?php echo $lang['Pages']; ?>
				
				<?php
				
				for($i = 0; $i < $pages; $i++ )
				{
					if($i != ($page - 1))
						echo '<a href="'.forum_link($forum_url['admin_management_events'].'&p='.($i + 1)).'">'.strval($i + 1).'</a> | ';
					else
						echo '<strong>'.strval($page).'</strong> | ';
				}
				
				?>
				
				</h2>
			</span>
		</div>
		<div class="main-head">
			<p><span class="item-info"><?php echo $lang['Results']; ?></span></p>
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
			
			foreach($data as $row)
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
						echo '<td class="tc'.$event_iter.'">'.$row[$key].'</td>';
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
			<p><span class="item-info"><?php echo $lang['Results']; ?></span></p>
		</div>
		<div class="main-pagepost gen-content">
			<span>
				<h2 class="hn"><?php echo $lang['Pages'].':' ?>
				
				<?php
				
				for($i = 0; $i < $pages; $i++ )
				{
					if($i != ($page - 1))
						echo '<a href="'.forum_link($forum_url['admin_management_events'].'&p='.($i + 1)).'">'.strval($i + 1).'</a> | ';
					else
						echo '<strong>'.strval($page).'</strong> | ';
				}
				
				?>
				
				</h2>
			</span>
		</div>
			
		<?php
	}
	else
	{
		?>
			<div class="ct-box">
				<p><strong><?php echo $lang['Nothing found'] ?></strong></p>
			</div>
		<?php
	}
}

function generate_dropdown_list($fld_id, $fld_name, $value_from, $value_to, $default_text)
{
	$result = '<select id="'.$fld_id.'" name="'.$fld_name.'">';
		for($i = $value_from; $i <= $value_to; $i++)
		{
			if((isset($_POST[$fld_name]) && ($_POST[$fld_name]) == $i) || ($i == $default_text))
				$result .= '<option selected="selected" value="'.$i.'">'.$i.'</option>'; 
			else
				$result .= '<option value="'.$i.'">'.$i.'</option>'; 
		}
	$result .= '</select>';

	echo $result;
}

function pun_events_generate_where()
{
	$result = array();
	global $forum_db;
	
	if (isset($_POST['day_from']) && isset($_POST['month_from']) && isset($_POST['year_from']) &&
		isset($_POST['day_to']) && isset($_POST['month_to']) && isset($_POST['year_to']))
	{
		if (($_POST['day_from'] > $_POST['day_to']) || ($_POST['month_from'] > $_POST['month_to']) || ($_POST['year_from'] > $_POST['year_to']))
		{
			$event_temp = $_POST['day_from'];
			$_POST['day_from'] = $_POST['day_to'];
			$_POST['day_to'] = $event_temp;
			
			$event_temp = $_POST['month_from'];
			$_POST['month_from'] = $_POST['month_to'];
			$_POST['month_to'] = $event_temp;
			
			$event_temp = $_POST['year_from'];
			$_POST['year_from'] = $_POST['year_to'];
			$_POST['year_to'] = $event_temp;
		}
		else if (($_POST['day_from'] == $_POST['day_to']) && ($_POST['month_from'] == $_POST['month_to']) && ($_POST['year_from'] == $_POST['year_to']))
			$_POST['day_to']++;
		
		$result[] = 'date >= STR_TO_DATE(\''.$_POST['day_from'].'/'.$_POST['month_from'].'/'.$_POST['year_from'].'\', \'%d/%m/%Y\')';
		$result[] = 'date <= STR_TO_DATE(\''.$_POST['day_to'].'/'.$_POST['month_to'].'/'.$_POST['year_to'].'\', \'%d/%m/%Y\')';
	}
	
	if(isset($_POST['event_id']) && $_POST['event_id'] != '' && $_POST['event_id'] != 0)
		$result[] = '(type = "'.$forum_db->escape($_POST['event_id']).'")';
	
	if(isset($_POST['ip']) && $_POST['ip'] != '*')
		$result[] = '(ip like \''.str_replace('*', '%', $forum_db->escape($_POST['ip'])).'\')';

	if(isset($_POST['name']) && $_POST['name'] != '*')
		$result[] = '(user_name like \''.str_replace('*', '%', $forum_db->escape($_POST['name'])).'\')';

	if(isset($_POST['comment']) && $_POST['comment'] != '*')
		$result[] = '(comment like \''.str_replace('*', '%', $forum_db->escape($_POST['comment'])).'\')';
	
	return ($result);
}
?>