<table>
<thead>
<tr>
<th scope="col" class="title"><a href="?list=<?php echo $_GET['list']; ?>&amp;orderby=title">Event Title</a></th>
<th scope="col" class="edit">View</th>
</tr>
</thead>
<tbody>
<?php foreach ($this->events as $event) { ?>
	<tr>
		<td><?php echo $event['title']; ?></td>
		<td>View</td>
	</tr>
<? } ?>
</tbody>
</table>