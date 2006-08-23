<table>
<thead>
<tr>
<th scope="col" class="select">Select</th>
<th scope="col" class="title">Event Title</th>
<th scope="col" class="edit">Edit</th>
</tr>
</thead>
<tbody>
<?php
$oddrow = false;
foreach ($this->events as $event) {
	$edt = UNL_UCBCN::factory('eventdatetime');
	$edt->event_id = $event['id'];
	$edt->orderBy('starttime DESC');
	$instances = $edt->find();
	?>
		<tr<?php if ($oddrow) echo ' class="alt"'; ?>>
			<td class="select">
				<?php
				$che = UNL_UCBCN::factory('calendar_has_event');
				$che->event_id = $event['id'];
				if ($che->find()) {
					$che->fetch();
					echo $che->status;
				} else  {
					echo '<input type="checkbox" name="event['.$event['id'].']" />';
				} ?>
			</td>
			<td><span class='title' style="float:left;"><?php echo $event['title']; ?></span>
				<a style="float:right;" href="#" onclick="showHide('instances_<?php echo $event['id'];?>'); return false;"><?php echo $instances; ?> +</a>
				<div id='instances_<?php echo $event['id'];?>' style="display:none;clear:both;">
				<ul>
				<?php
					while ($edt->fetch()) {
						echo '<li>'.$edt->starttime.'</li>';
					}
				?>
				</ul>
				</div>
			</td>
			<td class="edit">
				<?php
				if ($event['uidcreated']==$_SESSION['_authsession']['username']) {
					echo '<a href="?action=createEvent&amp;id='.$event['id'].'">Edit</a></td>';
				} ?>
			</td>
		</tr>
	<? } ?>
</tbody>
</table>