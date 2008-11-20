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
	if (!empty($data))
	{
		echo '<div class="main-head">';
		echo $lang['Results'];
		echo '</div>';
		echo '<div class="frm-head">';
		echo '<span>'.$lang['Pages'].':</span>';
		for($i = 0; $i < $pages; $i++ )
		{
			if($i != $page)
				echo '<a href="javascript:void(0)" onclick="javascript:PageSubmit('.$form_name.', '.$i.')">'.strval($i + 1).'</a> | ';
			else
				echo '<strong>'.strval($page + 1).'</strong> | ';
		}
		?>
		</div>
		<table cellspacing="0" summary="Table summary?>">
		<thead>
			<tr>
				<?php
				foreach($fields as $key => $value)
					echo '<th class="tcr" scope="col">'.$value.'</th>';
				?>
			</tr>
		</thead>
		<tbody>
		<?php
		foreach($data as $row)
		{
		?>
			<tr class="odd">
				<?php
				foreach($fields as $key => $value)
					echo '<td class="tcr">'.$row[$key].'</td>';
				?>
			</tr>
		<?php
		}
		?>
			</tbody>
		</table>
		<?php
		echo '<div class="main-head">';
		echo $lang['Results'];
		echo '</div>';
		echo '<div class="frm-head">';
		echo '<span>'.$lang['Pages'].':</span>';
		for($i = 0; $i < $pages; $i++ )
		{
			if($i != $page)
				echo '<a href="javascript:void(0)" onclick="javascript:PageSubmit('.$form_name.','.$i.')">'.strval($i + 1).'</a> | ';
			else
				echo '<strong>'.strval($page + 1).'</strong> | ';
		}
		?>
			</div>
		<?php
	}
	else
	{
		?>
		<div class="frm-info">
			<p><strong><?php echo $lang['Nothing found'] ?></strong></p>
		</div>
		<?php
	}
}

function generate_dropdown_list($fld_id, $fld_name, $value_from, $value_to, $default_text)
{
	$result = '<select id="'.$fld_id.'" name="'.$fld_name.'">';
		if(isset($_POST[$fld_name]) && ($_POST[$fld_name]) != 0)
			$result .= '<option value="0">'.$default_text.'</option>';
		else
			$result .= '<option selected="selected" value="0">'.$default_text.'</option>';

		for($i = $value_from; $i <= $value_to; $i++)
		{
			if(isset($_POST[$fld_name]) && ($_POST[$fld_name]) == $i)
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
	
	if(isset($_POST['year_from']) && $_POST['year_from'] != 0)
		$result[] = 'date >= STR_TO_DATE(\''.$_POST['day_from'].'/'.$_POST['month_from'].'/'.$_POST['year_from'].'\', \'%d/%m/%Y\')';

	if(isset($_POST['year_to']) && $_POST['year_to'] != 0)
		$result[] = 'date <= STR_TO_DATE(\''.$_POST['day_to'].'/'.$_POST['month_to'].'/'.$_POST['year_to'].'\', \'%d/%m/%Y\')';

	if(isset($_POST['event_id']) && $_POST['event_id'] != '')
		$result[] = 'type = \''.$forum_db->escape($_POST['event_id']).'\'';

	
	if(isset($_POST['ip']) && $_POST['ip'] != '*')
		$result[] = 'ip like \''.str_replace('*', '%', $forum_db->escape($_POST['ip'])).'\'';

	if(isset($_POST['name']) && $_POST['name'] != '*')
		$result[] = 'user_name like \''.str_replace('*', '%', $forum_db->escape($_POST['name'])).'\'';

	if(isset($_POST['comment']) && $_POST['comment'] != '*')
		$result[] = 'comment like \''.str_replace('*', '%', $forum_db->escape($_POST['comment'])).'\'';
	
	return ($result);
}
?>