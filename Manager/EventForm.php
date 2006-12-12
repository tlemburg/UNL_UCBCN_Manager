<?php

class UNL_UCBCN_Manager_EventForm
{
    
    function __construct(UNL_UCBCN_Manager $manager)
    {
        $this->manager =& $manager;
    }
    
    /**
     * Returns an HTML form for an event.
     *
     * @param int $id
     * @return string | object HTML on success UNL_UCBCN_Error on failure.
     */
    function toHtml($id=null)
    {
        $events = $this->manager->factory('event');
		if (isset($id)) {
			if (!$events->get($id)) {
				return new UNL_UCBCN_Error('Error, the event with that record was not found!');
			}
		}
		$fb = UNL_UCBCN_Manager_FormBuilder::create($events,false,'UCBCN_QuickForm','UNL_UCBCN_Manager_FormBuilder');
		$form = $fb->getForm($this->manager->uri.'?action=createEvent');
		
		if (isset($events->id)) {
		    $form->insertElementBefore(HTML_QuickForm::createElement('header','eventlocationheader','Event Location, Date and Time'),'optionaldetailsheader');
		    $form->insertElementBefore(HTML_QuickForm::createElement('static','datestimes',$this->getRelatedLocationDateAndTimes($events)),
		        'optionaldetailsheader');
		}
		
		$renderer =& new HTML_QuickForm_Renderer_Tableless();
		$renderer->addStopFieldsetElements(array(
													'__submit__'
													));
		$form->accept($renderer);
		$form->setDefaults(array(
					'uidcreated'		=> $this->manager->user->uid,
					'uidlastupdated'	=> $this->manager->user->uid));
		if ($form->validate()) {
			// Form has passed the client/server validation and can be inserted.
			/* If this is an update, first check to see if the current user has permission to edit
			 * events for the calendar the event was originally posted on.
			 */
			$result = $form->process(array(&$fb, 'processForm'), false);
			if ($result) {
				// EVENT Has been added... now check permissions and add to selected calendars.
				$add = $this->addToCalendar($events);
				if ($add===true) {
				    $this->manager->localRedirect($this->manager->uri.'?list=posted&new_event_id='.$events->id);
				} else {
				    // Probably a permissions error.
				    return $add; 
				}
			}
			$form->freeze();
			$form->removeElement('__submit__');
		}
		return $renderer->toHtml();
    }
    
    function addToCalendar($event)
    {
        $che =& UNL_UCBCN::calendarHasEvent($this->manager->calendar,$event);
		if ($che===false) {
			// This calendar does not already have this event.
			switch (true) {
				case $this->manager->userHasPermission($this->manager->user,'Event Post',$this->manager->calendar):
					$this->manager->addCalendarHasEvent($this->manager->calendar,$event,'posted',$this->manager->user,'create event form');
				break;
				case $this->manager->userHasPermission($this->manager->user,'Event Send Event to Pending Queue',$this->manager->calendar):
					$this->manager->addCalendarHasEvent($this->manager->calendar,$event,'pending',$this->manager->user,'create event form');
				break;
				default:
					return new UNL_UCBCN_Error('Sorry, you do not have permission to post an event, or send an event to the Calendar "'.$this->manager->calendar->name.'".');
			}
		} else {
			$che->uidlastupdated = $this->manager->user->uid;
			$che->datelastupdated = date('Y-m-d H:i:s');
			$che->update();
		}
		return true;
    }
    
    /**
     * Returns a html table of event date time and locations
     *
     * @param UNL_UCBCN_Event $event
     * @return string html
     */
    function getRelatedLocationDateAndTimes($event)
    {
		$edt = UNL_UCBCN::factory('eventdatetime');
		$edt->selectAdd('UNIX_TIMESTAMP(starttime) AS starttimeu, UNIX_TIMESTAMP(endtime) AS endtimeu');
		$edt->event_id = $event->id;
		$edt->orderBy('starttime DESC');
		$instances = $edt->find();
		if ($instances) {
		    require_once 'HTML/Table.php';
	        $table = new HTML_Table(array('class'=>'eventlisting'));
	        $table->addRow(array('Start Time','End Time','Location','Edit','Delete'),null,'TH');
	        $instances = 0;
		    while ($edt->fetch()) {
		        if (isset($edt->location_id)) {
		            $l = $edt->getLink('location_id');
		            $location = $l->name;
		        } else {
		            $location = 'Unknown';
		        }
		        if ($instances) {
		            $delete = '<a onclick="return confirm(\'Are you sure?\');" href="'.$this->manager->uri.'?action=eventdatetime&delete='.$edt->id.'">Delete</a>';
		        } else {
		            $delete = '';
		        }
			    $instances = $table->addRow(array(date('M jS g:ia',$edt->starttimeu),
			                        date('M jS g:ia',$edt->endtimeu),
			                        $location,
			                        '<a href="'.$this->manager->uri.'?action=eventdatetime&id='.$edt->id.'">Edit</a>',
			                        $delete));
			}
			$table->addRow(array('<a class="subsectionlink" href="'.$this->manager->uri.'?action=eventdatetime&event_id='.$event->id.'">Add additional location, date and time.</a>'));
			$table->setColAttributes(3,'class="edit"');
			$table->setColAttributes(4,'class="delete"');
			return $table->toHtml();
		} else {
		    return 'Could not find any related event date and times.';
		}
    }
}

?>