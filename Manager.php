<?php
/**
 * This class extends the UNL UCBerkeley Calendar backend system to create
 * a management frontend. It handles authentication for the user and allows
 * insertion of event details into the calendar backend.
 * 
 * @package UNL_UCBCN_Manager
 * @author bbieber
 */

require_once 'UNL/UCBCN.php';
require_once 'UNL/UCBCN/Manager/FormBuilder.php';
require_once 'HTML/QuickForm.php';
require_once 'Auth.php';
require_once 'UNL/UCBCN/EventListing.php';
// Custom quickform renderer.
require_once 'UNL/UCBCN/Manager/Tableless.php';
require_once 'UNL/UCBCN/Manager/Login.php';
require_once 'UNL/UCBCN/Manager/FormBuilder_Driver.php';

class UNL_UCBCN_Manager extends UNL_UCBCN {

	/** Auth object */
	var $a;
	/** Account */
	var $account;
	/** Calendar */
	var $calendar;
	/** User object */
	var $user;
	/** URI to the management frontend */
	public $uri = '';
	/** URI to the public frontend UNL_UCBCN_Frontend */
	public $frontenduri = '';
	/** Navigation */
	public $navigation;
	/** Account on right column */
	public $accountright;
	/** Unique body ID */
	public $uniquebody;
	/** Main content of the page sent to the client. */
	public $output;
	/** Page Title */
	public $doctitle;
	/** Section Title */
	public $sectitle;
	
	/**
	 * Constructor for the UNL_UCBCN_Manager.
	 * 
	 * @param array $options Associative array with options to set for member variables.
	 * 
	 */
	function __construct($options)
	{
		$this->setOptions($options);
		$this->setupDBConn();
		if (!isset($this->a)) {
			$this->a = new Auth('File', array('file'=>'@DATA_DIR@/UNL_UCBCN_Manager/admins.txt'), 'loginFunction',false);
		}
		if (isset($_GET['logout'])) {
			$this->a->logout();
		}
		$this->a->start();
		if ($this->a->checkAuth()) {
			// User has entered correct authentication details, now find get their user record.
			$this->user			= $this->getUser($this->a->getUsername());
			$this->account		= $this->getAccount($this->user);
			$this->calendar		= $this->getCalendar($this->user,$this->account,false,'?action=account&new=true');
		}
	}
	
	/**
	 * Returns a html snippet for the navigation.
	 * 
	 * @return html unordered list.
	 */
	function showNavigation()
	{
		return	'<ul>'."\n".
				'<li id="calendar"><a href="'.$this->uri.'?" title="My Calendar">Pending Events</a></li>'."\n".
				'<li id="create"><a href="'.$this->uri.'?action=createEvent" title="Create Event">Create Event</a></li>'."\n".
				//'<li id="search"><a href="'.$this->uri.'?action=search" title="Search">Search</a></li>'."\n".
				//'<li id="subscribe"><a href="'.$this->uri.'?action=subscribe" title="Subscribe">Subscribe</a></li>'."\n".
				//'<li id="import"><a href="'.$this->uri.'?action=import" title="Import/Export">Import/Export</a></li>'."\n".
				'</ul>'."\n";
	}
	
	/**
	 * Returns a html snippet for the account section.
	 * 
	 * @return html unordered list.
	 */
	function showAccountRight()
	{
		return	'<p id="date">'.date("F jS, Y").'</p>'."\n".
				'<div id="account_box">'."\n".
				'<p>Welcome, '.$this->user->uid.'</p>'."\n".
				'<ul>'."\n".
				'<li><a href="'.$this->frontenduri.'?calendar_id='.$this->calendar->id.'">Live Calendar</a></li>'."\n".
				'<li><a href="'.$this->uri.'?action=account">Account Info</a></li>'."\n".
				'<li><a href="'.$this->uri.'?action=calendar">Calendar Info</a></li>'."\n".
				'<li><a href="'.$this->uri.'?logout=true">LogOut</a></li>'."\n".
				'<li><a href="#">Help</a></li>'."\n".
				'</ul>'."\n".
				'</div>';
	}
	
	/**
	 * Returns login object which will be used for the user to authenticate with.
	 * 
	 * @return object UNL_UCBCN_Manager_Login.
	 */
	function showLoginForm()
	{
		return new UNL_UCBCN_Manager_Login();
		
	}
	
	/**
	 * Returns a form for entering/editing an event.
	 * 
	 * @param int ID of the event to retrieve and generate a form for.
	 * @return string HTML form for entering an event into the database.
	 */
	function showEventSubmitForm($id = NULL)
	{
		$events = $this->factory('event');
		if (isset($id)) {
			if (!$events->get($id)) {
				return new UNL_UCBCN_Error('Error, the event with that record was not found!');
			}
		}
		$fb = UNL_UCBCN_Manager_FormBuilder::create($events,false,'UCBCN_QuickForm','UNL_UCBCN_Manager_FormBuilder');
		$form = $fb->getForm($this->uri.'?action=createEvent');
		$renderer =& new HTML_QuickForm_Renderer_Tableless();
		$renderer->addStopFieldsetElements(array(
													'__submit__'
													));
		$form->accept($renderer);
		$form->setDefaults(array(
					'datecreated'		=> date('Y-m-d H:i:s'),
					'uidcreated'		=> $this->user->uid,
					'uidlastupdated'	=> $this->user->uid));
		if ($form->validate()) {
			// Form has passed the client/server validation and can be inserted.
			$result = $form->process(array(&$fb, 'processForm'), false);
			if ($result) {
				// EVENT Has been added... now check permissions and add to selected calendars.
				switch (true) {
					case $this->userHasPermission($this->user,'Event Post',$this->calendar):
						$this->addCalendarHasEvent($this->calendar,$events,'posted',$this->user);
					break;
					case $this->userHasPermission($this->user,'Event Send Event to Pending Queue',$this->calendar):
						$this->addCalendarHasEvent($this->calendar,$events,'pending',$this->user);
					break;
					default:
						return new UNL_UCBCN_Error('Sorry, you do not have permission to post an event, or send an event to the Calendar "'.$this->calendar->name.'".');
				}
				$this->localRedirect($this->uri.'?list=posted&new_event_id='.$events->id);
			}
			$form->freeze();
			$form->removeElement('__submit__');
		}
		return $renderer->toHtml();
	}
	
	/**
	 * Returns a html form for importing xml/.ics files.
	 * 
	 * @return string HTML form for uploading a file.
	 */
	function showImportForm()
	{
		$form = new HTML_QuickForm('import','POST',$this->uri.'?action=import');
		$form->addElement('header','importhead','Import iCalendar .ics/xml:');
		$form->addElement('file','filename','Filename');
		$form->addElement('submit','Submit','Submit');
		$renderer =& new HTML_QuickForm_Renderer_Tableless();
		$form->accept($renderer);
		return $renderer->toHtml();
	}
	
	/**
	 * This function is the hub for the manager frontend.
	 * All output sent to the client is set up here, based
	 * on querystring parameters and authentication level.
	 * 
	 * @param string $action A manual action to send to the client.
	 * @return none.
	 */
	function run($action='')
	{
		$this->doctitle = 'UNL Event Publishing System';
		if (isset($this->user)) {
			// User is authenticated, and an account has been chosen.
			$this->navigation = $this->showNavigation();
			$this->accountright = $this->showAccountRight();
			
			if (empty($action) && isset($_GET['action'])) {
				$action = $_GET['action'];
			}
			switch($action)
			{
				case 'createEvent':
					$this->uniquebody = 'id="create"';
					$this->sectitle = 'Create/Edit Event';
					if ($this->userHasPermission($this->user,'Event Create',$this->calendar)) {
						if (isset($_GET['id'])) {
							$id = (int)$_GET['id'];
						} else {
							$id = NULL;
						}
						$this->output = $this->showEventSubmitForm($id);
					} else {
						$this->output = new UNL_UCBCN_Error('Sorry, you do not have permission to create events. Are the event permissions in the database?');
					}
				break;
				case 'import':
					$this->output = $this->showImportForm();
					$this->uniquebody = 'id="import"';
					$this->sectitle = 'Import .ics or .xml Event';
				break;
				case 'search':
					$this->uniquebody = 'id="search"';
					$this->sectitle = 'Event Search';
				break;
				case 'subscribe':
					$this->uniquebody = 'id="subscribe"';
					$this->sectitle = 'Subscribe to Events';
				break;
				case 'account':
					$this->output = array();
					if (isset($_GET['new']) && $_GET['new']=='true') {
						$this->output[] =	'<p>Welcome to the University Event publishing system!</p>'.
											'<p>We\'ve created an account for you, simply enter in the additional details to begin publishing your events!</p>';
					}
					$this->output[] = $this->showAccountForm();
					$this->output[] = '<h3>Calendars Under This Account:</h3>';
					$this->output[] = $this->showCalendars();
					$this->sectitle = 'Edit '.$this->account->name.' Info';
				break;
				case 'permissions':
					$this->sectitle = 'Edit User Permissions for '.$this->calendar->name;
					if (isset($_GET['uid'])) {
						$uid = $_GET['uid'];
					} else {
						$uid = NULL;
					}
					$this->output = $this->showPermissionsForm($uid,$this->calendar);
				break;
				case 'calendar':
					$this->output = array();
					$this->output[] = $this->showCalendarForm();
					$this->output[] = '<h3>Users With Access to this Calendar:</h3>';
					$this->output[] = $this->showCalendarUsers();
					$this->sectitle = 'Edit '.$this->calendar->name.' Info';
				break;
				default:
					$this->uniquebody = 'id="normal"';
					if (isset($_GET['list'])) {
						$list = $_GET['list'];
					} else {
						$list = 'pending';
					}
					if (isset($_GET['orderby'])) {
						$orderby = $_GET['orderby'];
					} else {
						$orderby = NULL;
					}
					switch ($list) {
						case 'pending':
						case 'posted':
						case 'archived':
							$this->sectitle = ucfirst($list).' Events';
							$this->output[] = $this->showEventListing($list,$orderby);
						break;
						default:
							$this->output[] = $this->showEventListing('pending',$orderby);
							$this->sectitle = 'Pending Events';
						break;
					}
				break;
			}
		} else {
			// User is not logged in.
			$this->sectitle = 'Event Manager Login';
			$this->uniquebody = 'id="login"';
			$this->output = $this->showLoginForm();
		}
		$this->doctitle .= ' | '.$this->sectitle;
	}
	
	/**
	 * This function generates and returns a permissions form for the given user and calendar.
	 * 
	 * @param string|UNL_UCBCN_user 
	 * @param UNL_UCBCN_Calendar
	 */
	function showPermissionsForm($uid,$calendar)
	{
		if ($this->userHasPermission($this->user,'Calendar Change User Permissions',$this->calendar)) {
			$msg = '';
			if (!is_object($uid)) {
				$user = $this->factory('user');
				if (isset($uid) && !empty($uid)) {
					$user->uid = $uid;
					if ($user->find() && $user->fetch()) {
						//success	
					} else {
						return new UNL_UCBCN_Error('Sorry, no user with that uid could be found!');
					}
				} elseif($this->userHasPermission($this->user,'Calendar Add User',$this->calendar)) {
					// uid is not set, must be creating a new user..?
					$msg = 'Please select a new user to grant access to and choose the permissions you wish to grant.';
				} else {
					return new UNL_UCBCN_Error('You do not have permission to add new users to this calendar.');
				}
			} else {
				$user = $uid;
			}
			DB_DataObject::debugLevel(5);
			$fb = DB_DataObject_FormBuilder::create($user);
			if (!isset($user->uid)) {
				$fb->enumFields = array('uid');
				$uids = array();
				foreach (array_values($this->a->listUsers()) as $key=>$val) {
					$uids[$val['username']] = $val['username'];
				}
				$fb->enumOptions = array('uid'=>$uids);
			}
			$fb->formHeaderText = $user->uid.' Permissions for '.$this->calendar->name;
			$fb->crossLinks = array(array('table'=>'user_has_permission'));
			$fb->fieldLabels = array(	'__crossLink_user_has_permission_user_uid_permission_id'=>'Permissions',
										'uid'=>'User ID');
			$form = $fb->getForm('?action=permissions&uid='.$user->uid);
			$renderer =& new HTML_QuickForm_Renderer_Tableless();
			$form->setDefaults(array('account_id'=>$this->account->id));
			$form->accept($renderer);
			if ($form->validate()) {
				echo 'IN HIZZA!';
				//$this->createUser($this->account,$_POST['uid'],$this->user);
				$e = $form->getElement('uid');
				print_r($e->getValue());
				$uid = $e->getValue();
				$uid = $uid[0];
				$user = $this->getUser($uid);
				$form->process(array(&$fb, 'processForm'), false);
				$form->freeze();
				$form->removeElement('__submit__');
				$msg = '<p>User permissions saved...</p>';
			}
			DB_DataObject::debugLevel(0);
			return $msg.$renderer->toHtml();
		} else {
			return new UNL_UCBCN_Error('You do not have permission to edit permissions for this calendar!');
		}
	}
	
	/**
	 * Shows the list of events for the current user.
	 * 
	 * @param string $type The type of events to return, pending, posted or archived
	 * 
	 * @return array mixed, navigation list, events currently in the system.
	 */
	function showEventListing($status='pending',$orderby='starttime')
	{
		$e = array();
		$a_event = $this->factory('calendar_has_event');
		$event = $this->factory('event');
		$eventdatetime = $this->factory('eventdatetime');
		$event->joinAdd($eventdatetime);
		$a_event->joinAdd($event);
		switch($orderby) {
			case 'starttime':
				$a_event->orderBy('eventdatetime.starttime DESC');
			break;
			case 'title':
				$a_event->orderBy('event.title ASC');
			break;
			default:
				$a_event->orderBy('calendar_has_event.datecreated DESC');
			break;
		}
		$a_event->status = $status;
		$a_event->calendar_id = $this->calendar->id;
		if ($a_event->find()) {
			$listing = new UNL_UCBCN_EventListing();
			$listing->status = $status;
			while ($a_event->fetch()) {
				$event = $a_event->getLink('event_id');
				if (isset($_POST['event'][$event->id])) {
					// This event date time combination was selected... find out what they chose.
					if (isset($_POST['delete']) 
						&& $this->userHasPermission($this->user,'Event Delete',$this->calendar)) {
						// User has chosen to delete the event selected, and has permission to delete from pending.
						$a_event->delete();
						$eventdatetime->delete();
					} elseif (isset($_POST['pending'])
						&& $this->userHasPermission($this->user,'Event Send Event to Pending Queue',$this->calendar)) {
						$a_event->status = 'pending';
						$a_event->update();
					} elseif (isset($_POST['posted'])
						&& $this->userHasPermission($this->user,'Event Post',$this->calendar)) {
						$a_event->status = 'posted';
						$a_event->update();
					}
				} else {
					$listing->events[] = $event;
				}
			}
			$e[] = $listing;
		} else {
			$e[] = '<p>Sorry, there are no '.$status.' events.</p><p>Perhaps you would like to create some?<br />Use the <a href="?action=createEvent">Create Event interface.</a></p>';
		}
		array_unshift($e, '<ul>' .
							'<li><a href="'.$this->uri.'?list=pending">Pending ('.$this->getEventCount($this->calendar,'pending').')</a></li>' .
							'<li><a href="'.$this->uri.'?list=posted">Posted ('.$this->getEventCount($this->calendar,'posted').')</a></li>' .
							'<li><a href="'.$this->uri.'?list=archived">Archived ('.$this->getEventCount($this->calendar,'archived').')</a></li>' .
						'</ul>');
		return $e;
	}
	
	/**
	 * Returns a form to edit the current acccount.
	 * @return string html form.
	 */
	function showAccountForm()
	{
		if (isset($this->account)) {
			$msg = '';
			$fb = DB_DataObject_FormBuilder::create($this->account);
			if (!PEAR::isError($fb)) {
				$form = $fb->getForm('?action=account');
				$renderer =& new HTML_QuickForm_Renderer_Tableless();
				$form->accept($renderer);
				if ($form->validate()) {
					$form->process(array(&$fb, 'processForm'), false);
					$form->freeze();
					$form->removeElement('__submit__');
					$msg = '<p>Account info saved...</p>';
				}
				return $msg.$renderer->toHtml();
			} else {
				return new UNL_UCBCN_Error('showAccountForm could not create a formbuilder object! The error it returned was:'.$fb->message);
			}
		} else {
			return $this->showCalendars();
		}
	}
	
	/** This function returns a form for editing the calendar details.
	 * 
	 */
	 function showCalendarForm()
	 {
	 	if (isset($this->calendar)) {
	 		$fb = DB_DataObject_FormBuilder::create($this->calendar);
			$form = $fb->getForm($this->uri.'?action=calendar');
			$renderer =& new HTML_QuickForm_Renderer_Tableless();
			$form->accept($renderer);
			if ($form->validate()) {
				$form->process(array(&$fb, 'processForm'), false);
				$form->freeze();
				$form->removeElement('__submit__');
				$msg = '<p>Calendar info saved...</p>';
			}
			return $renderer->toHtml();
	 	} else {
	 		return $this->showChooseCalendar();
	 	}
	 }
	
	/**
	 * This function returns a list of calendars for the current account.
	 */
	function showCalendars()
	{
		$calendars = $this->factory('calendar');
		$calendars->account_id = $this->account->id;
		if ($calendars->find()) {
			$l = array('<ul>');
			while ($calendars->fetch()) {
				$l[] = '<li>'.$calendars->name.'&nbsp;<a href="'.$this->uri.'?action=calendar&amp;id='.$calendars->id.'">Edit</a></li>';
			}
			$l[] = '</ul>';
			return implode("\n",$l);
		} else {
			return new UNL_UCBCN_Error('Error, no calendars exist for the current account!');
		}
	}
	
	/**
	 * This function returns a list of users that have 'some' 
	 * permission to the current calendar.
	 * 
	 * @return string html list of users.
	 */
	function showCalendarUsers()
	{
		$permissions_list = array('<ul>');
		$user_has_permission = $this->factory('user_has_permission');
		$user_has_permission->calendar_id = $this->calendar->id;
		$users = $this->factory('user');
		$users->groupBy('uid');
		$users->joinAdd($user_has_permission);
		if ($users->find()) {
			while ($users->fetch()) {
				if ($this->userHasPermission($this->user,'Calendar Change User Permissions',$this->calendar)) {
					$user_li = '<li><a href="?action=permissions&amp;uid='.$users->uid.'">'.$users->uid.'</a></li>';
				} else {
					$user_li = '<li>'.$users->uid.'</li>';
				}
				$permissions_list[] = $user_li;
			}
		}
		$permissions_list[] = '</ul>';
		return implode("\n",$permissions_list);
	}

	/**
	 * This function returns all the calendars this user has access to.
	 * 
	 * @return html form for choosing account
	 */
	function showChooseCalendar()
	{
		$output = '<p>Please choose the calendar you wish to manage.</p>';
		$user_has_permission = $this->factory('user_has_permission');
		$user_has_permission->uid = $this->user->uid;
		if ($user_has_permission->find()) {
			$form = new HTML_QuickForm();
			$renderer =& new HTML_QuickForm_Renderer_Tableless();
			$form->accept($renderer);
			$form->addElement('header','calendarhead','Choose a Calendar:');
			$cal_select = HTML_QuickForm::createElement('select','calendar_id','Calendar');
			while ($user_has_permission->fetch()) {
				$cal_select->addOption($user_has_permission->calendar_id);
			}
			$form->addElement($cal_select);
			$form->addElement('submit','submit','Submit');
			$output .= $renderer->toHtml();
		} else {
			// Error, user has no permission to anything!
			$output = new UNL_UCBCN_Error('Sorry, you do not have permission to edit/access any calendars.');
		}
		return $output;
	}
}