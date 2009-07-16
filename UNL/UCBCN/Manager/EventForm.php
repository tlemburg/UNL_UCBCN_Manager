<?php
/**
 * Basic functions related to the event submission form.
 *
 * PHP version 5
 *
 * @category  Events
 * @package   UNL_UCBCN_Manager
 * @author    Brett Bieber <brett.bieber@gmail.com>
 * @copyright 2009 Regents of the University of Nebraska
 * @license   http://www1.unl.edu/wdn/wiki/Software_License BSD License
 * @link      http://code.google.com/p/unl-event-publisher/
 */
require_once 'UNL/UCBCN/Manager/jscalendar.php';

/**
 * This class generates a form for an Event.
 *
 * @category  Events
 * @package   UNL_UCBCN_Manager
 * @author    Brett Bieber <brett.bieber@gmail.com>
 * @copyright 2009 Regents of the University of Nebraska
 * @license   http://www1.unl.edu/wdn/wiki/Software_License BSD License
 * @link      http://code.google.com/p/unl-event-publisher/
 */
class UNL_UCBCN_Manager_EventForm
{
	public $event;
	public $eventtypes = array();
	public $sponsors = array();
	public $eventdatetime;
	public $action;

	/**
	 * Constructs the form.
	 *
	 * @param UNL_UCBCN_Manager $manager The manager object requesting the form.
	 */
	function __construct(UNL_UCBCN_Manager $manager, $id=null)
	{
		$this->manager =& $manager;
		$this->event = UNL_UCBCN::factory('event');
		$this->eventdatetime = UNL_UCBCN::factory('eventdatetime');
		if($id > 0){
			if(!$this->event->get($id)){
				return new UNL_UCBCN_Error('Error, the event with that record was not found!');
			}

			$eht = UNL_UCBCN::factory('event_has_eventtype');
			$eht->event_id = $id;
			if($eht->find()){
				while($eht->fetch()){
					$this->eventtypes[] = $eht->eventtype_id;
				}
			}

			$sponsor = UNL_UCBCN::factory('event_has_sponsor');
			$sponsor->event_id = $id;
			if($sponsor->find()){
				while($sponsor->fetch()){
					$this->sponsors[] = $sponsor->sponsor_id;
				}
			}

			$this->eventdatetime->event_id = $id;
			if($this->eventdatetime->find()){
				$this->eventdatetime->fetch();
			}
		}
			
		// Default the listingcontactuid to the currently logged in user
		if(empty($this->event->listingcontactuid)){
			$this->event->listingcontactuid = $this->manager->user->uid;
		}

		$this->action = $_SERVER['REQUEST_URI'];
	}

	/**
	 * Get all event types available
	 *
	 * @return mixed An object related to the eventtype table
	 */
	function getEventTypes()
	{
		$et = UNL_UCBCN::factory('eventtype');
		$et->find();
		return $et;
	}

	/**
	 * Get all sponsors available
	 *
	 * @return mixed An object related to the sponsor table
	 */
	function getSponsors()
	{
		$sponsor = UNL_UCBCN::factory('sponsor');
		$sponsor->find();
		return $sponsor;
	}

	/**
	 * Get all users available
	 *
	 * @return mixed An object related to the user table
	 */
	function getUsers()
	{
		$user = UNL_UCBCN::factory('user');
		$user->find();
		return $user;
	}

	/**
	 * Adds an event to the calendar currently in the manager member variable calendar.
	 *
	 * @param UNL_UCBCN_Event $event The event to add to the calendar.
	 *
	 * @return bool
	 */
	function addToCalendar(UNL_UCBCN_Event $event)
	{
		$che =& UNL_UCBCN::calendarHasEvent($this->manager->calendar, $event);
		if ($che===false) {
			// This calendar does not already have this event.
			switch (true) {
				case $this->manager->userHasPermission($this->manager->user, 'Event Post', $this->manager->calendar):
					$this->manager->addCalendarHasEvent($this->manager->calendar, $event, 'posted', $this->manager->user, 'create event form');
					break;
				case $this->manager->userHasPermission($this->manager->user, 'Event Send Event to Pending Queue', $this->manager->calendar):
					$this->manager->addCalendarHasEvent($this->manager->calendar, $event, 'pending', $this->manager->user, 'create event form');
					break;
				default:
					return new UNL_UCBCN_Error('Sorry, you do not have permission to post an event, or send an event to the Calendar "'.$this->manager->calendar->name.'".');
			}
		} else {
			$che->uidlastupdated  = $this->manager->user->uid;
			$che->datelastupdated = date('Y-m-d H:i:s');
			$che->update();
		}
		return true;
	}

	/**
	 * Returns a html table of event date time and locations
	 *
	 * @param UNL_UCBCN_Event $event The event to get related eventdatetime objects for.
	 *
	 * @return string html
	 */
	function getRelatedLocationDateAndTimes(UNL_UCBCN_Event $event)
	{
		$edt = UNL_UCBCN::factory('eventdatetime');
		$edt->selectAdd('UNIX_TIMESTAMP(starttime) AS starttimeu, UNIX_TIMESTAMP(endtime) AS endtimeu');
		$edt->event_id = $event->id;
		$edt->orderBy('starttime DESC');
		$instances = $edt->find();
		if ($instances) {
			include_once 'HTML/Table.php';
			$table = new HTML_Table(array('class'=>'eventlisting'));
			$table->addRow(array('Start Time', 'End Time', 'Location', 'Edit', 'Delete'), null, 'TH');
			$instances = 0;
			while ($edt->fetch()) {
				$etime = '';
				$stime = '';
				if (isset($edt->location_id)) {
					$l        = $edt->getLink('location_id');
					$location = $l->name;
				} else {
					$location = 'Unknown';
				}
				if ($instances) {
					$delete = '<a onclick="return confirm(\'Are you sure?\');" href="'.$this->manager->uri.'?action=eventdatetime&delete='.$edt->id.'">Delete</a>';
				} else {
					$delete = '';
				}
				if (substr($edt->starttime, 11) != '00:00:00') {
					$stime .= '<li>'.date('M jS g:ia', $edt->starttimeu).'</li>';
				} else {
					$stime .= '<li>'.date('M jS', $edt->starttimeu).'</li>';
				}
				if (substr($edt->endtime, 11) != '00:00:00') {
					$etime .= '<li>'.date('M jS g:ia', $edt->endtimeu).'</li>';
				} elseif ($edt->endtime != '0000-00-00 00:00:00') {
					$etime .= '<li>'.date('M jS', $edt->endtimeu).'</li>';
				}
				$instances = $table->addRow(array($stime,
				$etime,
				$location,
                                    '<a href="'.$this->manager->uri.'?action=eventdatetime&id='.$edt->id.'">Edit</a>',
				$delete));
			}
			$table->addRow(array('<a class="subsectionlink" href="'.$this->manager->uri.'?action=eventdatetime&event_id='.$event->id.'">Add additional location, date and time.</a>'));
			$table->setColAttributes(3, 'class="edit"');
			$table->setColAttributes(4, 'class="delete"');
			return $table->toHtml();
		} else {
			return 'Could not find any related event date and times.';
		}
	}
}

?>