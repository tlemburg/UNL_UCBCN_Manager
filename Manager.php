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

class UNL_UCBCN_Manager extends UNL_UCBCN {

	/** Auth object */
	var $a;
	/** Account */
	var $account;
	/** Calendar */
	var $calendar;
	/** User object */
	var $user;
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
			$this->calendar		= $this->getCalendar($this->user,false,'?action=account&new=true');
			$this->account		= $this->getAccount($this->calendar);
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
				'<li id="calendar"><a href="?" title="My Calendar">Pending Events</a></li>'."\n".
				'<li id="create"><a href="?action=createEvent" title="Create Event">Create Event</a></li>'."\n".
				'<li id="search"><a href="?action=search" title="Search">Search</a></li>'."\n".
				'<li id="subscribe"><a href="?action=subscribe" title="Subscribe">Subscribe</a></li>'."\n".
				'<li id="import"><a href="?action=import" title="Import/Export">Import/Export</a></li>'."\n".
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
				'<li><a href="?action=account">Account Info</a></li>'."\n".
				'<li><a href="?logout=true">LogOut</a></li>'."\n".
				'<li><a href="#">Help</a></li>'."\n".
				'</ul>'."\n".
				'</div>';
	}
	
	/**
	 * Returns a HTML form for the user to authenticate with.
	 * 
	 * @return html form for authenticating the user.
	 */
	function showLoginForm()
	{
		$intro = '<p>Welcome to the University Event Publishing System, please log in using your
					Username and Password.</p>';		
		$lostpassword = '<p id="lost"><a href="#" title="" id="forgot">Forgot your password?</a></p>';
		$form = new HTML_QuickForm('login');
		$form->addElement('text','username','User');
		$form->addElement('password','password','Password');
		$form->addElement('submit','submit','Submit');
		$renderer =& new HTML_QuickForm_Renderer_Tableless();
		$form->accept($renderer);
		return $intro.$renderer->toHtml().$lostpassword;
		
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
		$fb = UNL_UCBCN_Manager_FormBuilder::create($events,false,'QuickForm','UNL_UCBCN_Manager_FormBuilder');
		$fb->linkNewValue = array('__reverseLink_eventdatetime_event_idlocation_id_1','location_id');
		$fb->reverseLinks = array(array('table'=>'eventdatetime'));
		$fb->reverseLinkNewValue = true;
		$fb->linkElementTypes = array('__reverseLink_eventdatetime_event_id'=>'subForm');
		$form = $fb->getForm($_SERVER['PHP_SELF'].'?action=createEvent');
		$renderer =& new HTML_QuickForm_Renderer_Tableless();
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
						return UNL_UCBCN_Error('Sorry, you do not have permission to post an event, or send an event to the Calendar.');
				}
				$this->localRedirect('?list=posted&new_event_id='.$events->id);
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
		$form = new HTML_QuickForm('import','POST','?action=import');
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
						$this->output = new UNL_UCBCN_Error('Sorry, you do not have permission to create events.');
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
					if (isset($_GET['new']) && $_GET['new']=='true') {
						$this->output =		'<p>Welcome to the University Event publishing system!</p>'.
											'<p>We\'ve created an account for you, simply enter in the additional details to begin publishing your events!</p>';
					}
					$this->output .= $this->showAccountForm();
					$this->sectitle = 'Edit '.$this->account->name.' Info';
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
				if (isset($_POST['event'][$event->id]) 
					&& isset($_POST['delete']) 
					&& $this->userHasPermission($this->user,'Event Remove from Pending',$this->calendar)) {
					// User has chosen to delete the event selected, and has permission to delete from pending.
						$a_event->delete();
				} else {
					$listing->events[] = $event;
				}
			}
			$e[] = $listing;
		} else {
			$e[] = '<p>Sorry, there are no '.$status.' events.</p><p>Perhaps you would like to create some?<br />Use the <a href="?action=createEvent">Create Event interface.</a></p>';
		}
		array_unshift($e, '<ul>' .
							'<li><a href="?list=pending">Pending ('.$this->getEventCount($this->calendar,'pending').')</a></li>' .
							'<li><a href="?list=posted">Posted ('.$this->getEventCount($this->calendar,'posted').')</a></li>' .
							'<li><a href="?list=archived">Archived ('.$this->getEventCount($this->calendar,'archived').')</a></li>' .
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
			$form = $fb->getForm('?action=account');
			$renderer =& new HTML_QuickForm_Renderer_Tableless();
			$form->accept($renderer);
			if ($form->validate()) {
				$form->process(array(&$fb, 'processForm'), false);
				$form->freeze();
				$form->removeElement('__submit__');
				$msg = '<p>Account info saved...</p>';
			}
			return $msg.$renderer->toHtml().$this->showAccountUsers();
		} else {
			return $this->showAccounts();
		}
	}
	
	/**
	 * This function returns a list of users that have 'some' 
	 * permission to the current account.
	 * 
	 * @return string html list of users.
	 */
	function showAccountUsers()
	{
		$permissions_list = '<h4>User Permissions</h4>';
		$user_has_permission = $this->factory('user_has_permission');
		$user_has_permission->account_id = $this->account->id;
		$users = $this->factory('user');
		$users->groupBy('uid');
		$users->joinAdd($user_has_permission);
		if ($users->find()) {
			while ($users->fetch()) {
				$permissions_list .= $users->uid;
			}
		}
		return $permissions_list;
	}

	/**
	 * This function returns all the accounts this user has access to.
	 * 
	 * @return html form for choosing account
	 */
	function showAccounts()
	{
		$output = '<p>Please choose the account you wish to manage.</p>';
		$user_has_permission = $this->factory('user_has_permission');
		$user_has_permission->uid = $this->user->uid;
		if ($user_has_permission->find()) {
			$form = new HTML_QuickForm();
			$renderer =& new HTML_QuickForm_Renderer_Tableless();
			$form->accept($renderer);
			$form->addElement('header','accounthead','Choose an account:');
			$acc_select = HTML_QuickForm::createElement('select','account_id','Account');
			while ($user_has_permission->fetch()) {
				$acc_select->addOption($user_has_permission->account_id);
			}
			$form->addElement($acc_select);
			$form->addElement('submit','submit','Submit');
			$output .= $renderer->toHtml();
		} else {
			// Error, user has no permission to anything!
			$output = new UNL_UCBCN_Error('Sorry, you do not have permission to edit/access any accounts.');
		}
		return $output;
	}
}