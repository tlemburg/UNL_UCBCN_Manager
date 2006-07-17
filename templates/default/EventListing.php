<form action="?list=<?php echo $this->status; ?>" method="post">
<table>
<thead>
<tr>
<th scope="col" class="select">Select</th>
<th scope="col" class="date"><a href="?list=<?php echo $_GET['list']; ?>&amp;orderby=starttime">Date</a></th>
<th scope="col" class="title"><a href="?list=<?php echo $_GET['list']; ?>&amp;orderby=title">Event Title</a></th>
<th scope="col" class="edit">Edit</th>
</tr>
</thead>
<tbody>
<?php
$oddrow = false;
foreach ($this->events as $e) {
	$eventdatetime = $e->getLink('id','eventdatetime','event_id');
	$row = '<tr';
	if (isset($_GET['new_event_id']) && $_GET['new_event_id']==$e->id) {
		$row .= ' class="updated"';
	} elseif ($oddrow) {
		$row .= ' class="alt"';
	}
	$row .= '>';
	$oddrow = !$oddrow;
	$row .=	'<td class="select"><input type="checkbox" name="event['.$e->id.']" />' .
			'<td class="date">';
	if (isset($eventdatetime->starttime)) {
            $row .= $eventdatetime->starttime;
    } else {
            $row .= 'Unknown';
    }
	$row .= '</td>' .
			'<td class="title">'.$e->title.'</td>' .
			'<td class="edit"><a href="?action=createEvent&amp;id='.$e->id.'">Edit</a></td>' .
			'</tr>';
	echo $row;
} ?>
</tbody>
</table>
<input id="delete_event" type="submit" name="delete" value="Delete" />
<?php if ($this->status=='posted') { ?>
<input id="moveto_pending" type="submit" name="pending" value="Move to Pending" />
<?php } elseif ($this->status=='pending') { ?>
<input id="moveto_posted" type="submit" name="posted" value="Add to Posted" />
<?php } ?>
</form>