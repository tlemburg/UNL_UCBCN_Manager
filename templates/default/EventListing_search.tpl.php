<form action="<?php echo $_SERVER['PHP_SELF'].'?action=search&amp;q='.$_GET['q']; ?>" id="searchlist" method="post">
<table class="eventlisting">
<thead>
<tr>
<th scope="col" class="select">Select</th>
<th scope="col" class="title">Event Title</th>
<th scope="col" class="edit">Edit</th>
<th scope="col" class="delete">Delete</th>
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
				if ($event['calendarhasevent']===false) {
					echo '<input type="checkbox" name="event'.$event['id'].'" />';
				} else {
				    echo $event['calendarhasevent'];
				} ?>
			</td>
			<td class="title"><span class='title' style="float:left;"><?php echo $event['title']; ?></span>
				<div id='instances_<?php echo $event['id']; ?>' class="instances">
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
				if ($event['usercaneditevent']) {
					echo '<a href="?action=createEvent&amp;id='.$event['id'].'">Edit</a></td>';
				} ?>
			</td>
			<td class="delete">
				<?php
				if ($event['usercandeleteevent']) {
					echo '<a onclick="return confirm(\'Are you sure you wish to delete '.htmlentities($event['title']).'?\');" href="'.$_SERVER['PHP_SELF'].'?action=search&amp;q='.$_GET['q'].'&amp;delete='.$event['id'].'">Delete</a></td>';
				} ?>
			</td>
		</tr>
	<?php
	$oddrow = !$oddrow;
	} ?>
</tbody>
</table>
<a href="#" class="checkall" onclick="setCheckboxes('searchlist',true); return false">Check All</a>
<a href="#" class="uncheckall" onclick="setCheckboxes('searchlist',false); return false">Uncheck All</a>
<button id="moveto_pending" type="submit" name="pending" value="pending">Add to Pending</button>
<button id="moveto_posted" type="submit" name="posted" value="posted">Add to Posted</button>
</form>
