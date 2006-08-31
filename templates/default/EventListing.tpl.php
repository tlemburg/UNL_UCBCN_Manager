<form action="?list=<?php echo $this->status; ?>" name="formlist" method="post">
<table class="eventlisting">
<thead>
<tr>
<th scope="col" class="select">Select</th>
<th scope="col" class="title"><a href="?list=<?php echo $_GET['list']; ?>&amp;orderby=title">Event Title</a></th>
<th scope="col" class="date"><a href="?list=<?php echo $_GET['list']; ?>&amp;orderby=starttime">Date</a></th>
<th scope="col" class="edit">Edit</th>
</tr>
</thead>
<tbody>
<?php
$oddrow = false;
foreach ($this->events as $e) {
	$row = '<tr id="row'.$e->id.'"';
	if (isset($_GET['new_event_id']) && $_GET['new_event_id']==$e->id) {
		if ($oddrow){
		$row .= ' class="updated alt"';
		} else{
		$row .= ' class="updated"';	
		}
	} elseif ($oddrow) {
		$row .= ' class="alt"';
	}
	$row .= ' onclick="highlightLine(this,'.$e->id.');">';
	$oddrow = !$oddrow;
	$row .=	'<td class="select"><input type="checkbox" onclick="checknegate('.$e->id.')" name="event'.$e->id.'" />' .
			'<td class="title">'.$e->title.'</td>' .
			'<td class="date">';
	$edt = UNL_UCBCN::factory('eventdatetime');
	$edt->event_id = $e->id;
	$edt->orderBy('starttime DESC');
	$instances = $edt->find();
	if ($instances) {
		$row .= '<ul>';
			while ($edt->fetch()) {
            	$row .= '<li>'.$edt->starttime.'</li>';
			}
		$row .= '</ul>';
    } else {
            $row .= 'Unknown';
    }
	$row .= '</td>' .
			'<td class="edit">';
	if (UNL_UCBCN::userCanEditEvent($_SESSION['_authsession']['username'],$e)) {
		$row .= '<a href="?action=createEvent&amp;id='.$e->id.'">Edit</a>';
	}
	$row .=		'</td></tr>';
	echo $row;
} ?>
</tbody>
</table>
<button id="delete_event" type="submit" name="delete" value="delete">Delete</button>
<?php if ($this->status=='posted') { ?>
<button id="moveto_pending" type="submit" name="pending" value="pending">Move to Pending</button>
<?php } elseif ($this->status=='pending') { ?>
<button id="moveto_posted" type="submit" name="posted" value="posted">Add to Posted</button>
<?php } ?>
</form>