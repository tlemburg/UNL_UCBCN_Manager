<?php
/**
 * This class extends the UNL UCBerkeley Calendar backend system to create
 * a management frontend. It handles authentication for the user and allows
 * insertion of event details into the calendar backend.
 * It allows authenticated users to submit new events into the system.
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
	/** Indicates which calendars you have access to. */
	public $calendarselect;
	/** Unique body ID */
	public $uniquebody;
	/** Main content of the page sent to the client. */
	public $output;
	/** Page Title */
	public $doctitle;
	/** Section Title */
	public $sectitle;
	/** Registered and running plugins. */
	public $plugins = array();
	
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
			$this->endSession();
			$this->a->logout();
		}
		$this->a->start();
		if ($this->a->checkAuth()) {
			$this->startSession();
			$this->startupPlugins();
			UNL_UCBCN::archiveEvents($this->calendar);
		}
	}
	
	/**
	 * This function initializes all plugins.
	 * 
	 */
	function startupPlugins()
	{
		global $_UNL_UCBCN;
		$ds = DIRECTORY_SEPARATOR;
		$plugin_dir = '@PHP_DIR@'.$ds.'UNL'.$ds.'UCBCN'.$ds.'Manager'.$ds.'Plugins';
		if (is_dir($plugin_dir)) { 
			if ($handle = opendir($plugin_dir)) {
				while (false !== ($file = readdir($handle))) {
					if ($file != '.' && $file != '..') {
						include_once $plugin_dir.$ds.$file;
					}
				}
				closedir($handle);
			}
		}
		if (isset($_UNL_UCBCN['plugins'])) {
			foreach ($_UNL_UCBCN['plugins'] as $plug_class) {
				if (class_exists($plug_class)) {
					try {
						$plugin = new $plug_class();
						$plugin->startup($this,$this->uri.'?action=plugin&p='.$plug_class);
						$this->plugins[$plug_class] = $plugin;
					} catch(Exception $e) {
						echo 'Caught trying to start plugin \''.$plug_class.'\': ',  $e->getMessage(), "\n";
					}
				}
			}
		}
	}
	
	/**
	 * Begins a calendar management session for this user.
	 */
	function startSession()
	{
		// User has entered correct authentication details, now find get their user record.
		$this->user			= $this->getUser($this->a->getUsername());
		$this->session		= UNL_UCBCN::factory('session');
		$this->session->user_uid = $this->user->uid;
		if (!$this->session->find()) {
			$this->session->user_uid = $this->user->uid;
			$this->session->lastaction = date('Y-m-d H:i:s');
			$this->session->insert();
		} else {
			$this->session->fetch();
		}
		$this->account		= $this->getAccount($this->user);
		if (isset($_GET['calendar_id']) ||
			(isset($this->user->calendar_id) && ($this->user->calendar_id != 0))) {
			$this->calendar = $this->factory('calendar');
			if (isset($_GET['calendar_id'])) {
				$cid = $_GET['calendar_id'];
			} else {
				$cid = $this->user->calendar_id;
			}
			if (!$this->calendar->get($cid)) {
				// Could not get the calendar in the session or $_GET
				$this->calendar		= $this->getCalendar($this->user,$this->account,false,'?action=calendar&new=true');
			}
		} else {
			$this->calendar		= $this->getCalendar($this->user,$this->account,false,'?action=calendar&new=true');
		}
		if ($this->user->calendar_id != $this->calendar->id) {
			// Set the user's calendar_id to remember their default calendar.
			$this->user->calendar_id = $this->calendar->id;
			$this->user->update();
		}
		$_SESSION['calendar_id'] = $this->calendar->id;
	}
	
	/**
	 * Ends a calendar management session for the current user.
	 */
	function endSession()
	{
		unset($_SESSION['calendar_id']);
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
			/* If this is an update, first check to see if the current user has permission to edit
			 * events for the calendar the event was originally posted on.
			 */
			$result = $form->process(array(&$fb, 'processForm'), false);
			if ($result) {
				// EVENT Has been added... now check permissions and add to selected calendars.
				$che =& UNL_UCBCN::calendarHasEvent($this->calendar,$events);
				if ($che===false) {
					// This calendar does not already have this event.
					switch (true) {
						case $this->userHasPermission($this->user,'Event Post',$this->calendar):
							$this->addCalendarHasEvent($this->calendar,$events,'posted',$this->user,'create event form');
						break;
						case $this->userHasPermission($this->user,'Event Send Event to Pending Queue',$this->calendar):
							$this->addCalendarHasEvent($this->calendar,$events,'pending',$this->user,'create event form');
						break;
						default:
							return new UNL_UCBCN_Error('Sorry, you do not have permission to post an event, or send an event to the Calendar "'.$this->calendar->name.'".');
					}
				} else {
					$che->uidlastupdated = $this->user->uid;
					$che->datelastupdated = date('Y-m-d H:i:s');
					$che->update();
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
			$this->calendarselect[] = $this->showChooseCalendar();
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
						$this->output[] = $this->showEventSubmitForm($id);
					} else {
						$this->output= new UNL_UCBCN_Error('Sorry, you do not have permission to create events. Are the event permissions in the database?');
					}
				break;
				case 'import':
					$this->output[] = $this->showImportForm();
					$this->uniquebody = 'id="import"';
					$this->sectitle = 'Import .ics or .xml Event';
				break;
				case 'search':
					$this->uniquebody = 'id="search"';
					$this->sectitle = 'Event Search';
					UNL_UCBCN::outputTemplate('UNL_UCBCN_EventListing','EventListing_search');
					$this->output[] = $this->showSearchForm();
					$this->output[] = $this->showSearchResults();
				break;
				case 'subscribe':
					$this->uniquebody = 'id="subscribe"';
					$this->sectitle = 'Subscribe to Events';
					$this->output[] = '<p>Subscriptions allow you to automatically add events to your calendar which match a given set of criteria.
										This feature allows the College of Engineering\'s Calendar to automatically add all events posted to the Electrical Engineering calendar.</p>';
					$this->output[] = $this->showSubscriptions();
					$this->output[] = $this->showSubscribeForm();
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
					if (isset($_GET['new']) && $_GET['new']=='true') {
						$this->output[] =	'<p>Welcome to the University Event publishing system!</p>'.
											'<p>We\'ve created a calendar for you, simply enter in the additional details to begin publishing your events!</p>'.
											'<p>Your calendar name is the title of your calendar, and will be displayed with all your events.</p>';
					}
					$this->output[] = $this->showCalendarForm();
					$this->output[] = '<h3>Users With Access to this Calendar:</h3>';
					$this->output[] = $this->showCalendarUsers();
					if ($this->userHasPermission($this->user,'Calendar Add User',$this->calendar)) {
					    $this->output[] = $this->showAddUserForm();
					}
					$this->sectitle = 'Edit '.$this->calendar->name.' Info';
				break;
				case 'plugin':
					if (isset($_GET['p']) && isset($this->plugins[$_GET['p']])) {
						$this->plugins[$_GET['p']]->run();
						$this->output[] = $this->plugins[$_GET['p']]->output;
					} else {
						$this->output = new UNL_UCBCN_Error('That plugin does not exist.');
					}
				break;
				default:
					$this->uniquebody = 'id="normal"';
					if (isset($_GET['list'])) {
						$list = $_GET['list'];
					} else {
						$list = 'pending';
					}
					$orderby = '';
					if (isset($_GET['orderby'])) {
						$orderby = $_GET['orderby'];
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
	
	function showSubscribeForm()
	{
	    $subscription =& $this->factory('subscription');
	    if (isset($_GET['id'])) {
	        $subscription->get($_GET['id']);
	    }
	    $fb =& DB_DataObject_FormBuilder::create($subscription);
	    $form =& $fb->getForm($this->uri.'?action=subscribe');
	    if (isset($subscription->searchcriteria)) {
	        $form->setDefaults(array('calendar_id'=>$this->calendar->id,
	                                 'searchcriteria'=>$subscription->getCalendars($subscription->searchcriteria)));
	    } else {
	        $form->setDefaults(array('calendar_id'=>$this->calendar->id));
	    }
	    $renderer =& new HTML_QuickForm_Renderer_Tableless();
		$form->accept($renderer);
	    if ($form->validate()) {
	        if ((isset($subscription->id) && UNL_UCBCN::userHasPermission($this->user,'Calendar Edit Subscription',$this->calendar))
	                || UNL_UCBCN::userHasPermission($this->user,'Calendar Add Subscription',$this->calendar)) {
		        $form->process(array(&$fb, 'processForm'), false);
				$form->freeze();
				$form->removeElement('__submit__');
		        // Add new subscription.
		        return '<p>Your subscription has been added.</p>';
	        } else {
	            return new UNL_UCBCN_Error('You do not have permission to add/edit subscriptions!');
	        }
	    } else {
	        return $renderer->toHtml();
	    }
	}
	
	/**
	 * Returns a listing of the subscriptions for the current calendar.
	 * 
	 * @return string html list of subscriptions.
	 */
	function showSubscriptions()
	{ 
	    $subscriptions = $this->factory('subscription');
	    $subscriptions->calendar_id = $this->calendar->id;
	    if ($subscriptions->find()) {
	        $list = array('<ul>');
	        while ($subscriptions->fetch()) {
	            $li = '<li>'.$subscriptions->name;
	            // Provide Edit link if the user has permission.
	            if (UNL_UCBCN::userHasPermission($this->user,'Calendar Edit Subscription',$this->calendar)) {
	                $li .= ' <a href="'.$this->uri.'?action=subscribe&amp;id='.$subscriptions->id.'">Edit</a>';
	            }
	            // Show Delete link if the user has permission to delete.
	            if (UNL_UCBCN::userHasPermission($this->user,'Calendar Delete Subscription',$this->calendar)) {
         	        if (isset($_GET['delete']) && $_GET['delete']==$subscriptions->id) {
         	            if ($subscriptions->delete()) {
         	                $li = '<li>'.$subscriptions->name.' (Deleted)';
         	            } else {
         	                // error deleting the subscription?
         	                 $li = '<li>'.$subscriptions->name.' Error, cannot delete.';
         	            }
         	        } else {
         	            $li .= ' <a href="'.$this->uri.'?action=subscribe&amp;delete='.$subscriptions->id.'">Delete</a>';
         	        }
	            }
	            $list[] = $li.'</li>';
	        }
	        $list[]  = '</ul>';
	        return implode("\n",$list);
	    } else {
	        return 'This calendar currently has no subscriptions.';
	    }
	}
	
	/**
	 * Returns a search form for searching the events database.
	 */
	function showSearchForm()
	{
		$form = new HTML_QuickForm('event_search','get');
		$form->addElement('header','searchheader','Search Events');
		$form->addElement('hidden','action','search');
		$form->addElement('text','q','Search for events:');
		$form->addElement('static','','','<small style="color:#999;">"Tomorrow", "March 31st", "Earth Day"</small>');
		$form->addElement('xbutton','search','Submit','type="submit"');
		$renderer =& new HTML_QuickForm_Renderer_Tableless();
		$form->accept($renderer);
		return $renderer->toHtml();
	}
	
	/**
	 * Returns an event listing of search results.
	 */
	function showSearchResults()
	{
	    require_once 'UNL/UCBCN/Calendar_has_event.php';
		$q = (isset($_GET['q']))?$_GET['q']:NULL;
		$mdb2 =& $this->getDatabaseConnection();
		if (!empty($q)) {
			$events = $this->factory('event');
			if ($t = strtotime($q)) {
				// This is a time...
				$events->query('SELECT event.* FROM event, eventdatetime WHERE ' .
						'eventdatetime.event_id = event.id AND eventdatetime.starttime LIKE \''.date('Y-m-d',$t).'%\'');
				
			} else {
				// Do a textual search.
				$q = $mdb2->escape($q);
				$events->whereAdd('event.title LIKE \'%'.$q.'%\' AND event.approvedforcirculation=1');
				$events->orderBy('event.title');
				$events->find();
			}
			$listing = new UNL_UCBCN_EventListing();
			while ($events->fetch()) {
			    if (isset($_GET['delete']) 
			        && ($_GET['delete']==$events->id)
			        && UNL_UCBCN::userHasPermission($this->user,'Event Delete',$this->calendar)) {
			        $this->calendar->removeEvent($events);
			        if ($events->isOrphan()) {
			            $events->delete();
			        }
			    } else {
			        $this->processPostStatusChange($events);
			        if (UNL_UCBCN::userHasPermission($this->user,'Event Delete',$this->calendar)
			            && UNL_UCBCN_Calendar_has_event::calendarHasEvent($this->calendar,$events)) {
			            $candelete = true;
			        } else {
			            $candelete = false;
			        }
			        $listing->events[] =  array_merge($events->toArray(),array('usercaneditevent'=>UNL_UCBCN::userCanEditEvent($this->user,$events),
			                                                                    'usercandeleteevent'=>$candelete,
			                                                                    'calendarhasevent'=>UNL_UCBCN_Calendar_has_event::calendarHasEvent($this->calendar,$events)));
			    }
			}
			if (count($listing->events)) {
				return array('<p class="num_results">'.count($listing->events).' Result(s)</p>',$listing);
			} else {
				return '<p>No results found.</p>';
			}
		} else {
			return '';
		}
	}
	
	function processPostStatusChange($event,$source='search')
	{
	    if (isset($_POST['event'.$event->id])) {
		    $a_event = $this->factory('calendar_has_event');
		    $a_event->calendar_id = $this->calendar->id;
		    $a_event->event_id = $event->id;
		    if ($a_event->find() && $a_event->fetch()) {
				// This event date time combination was selected... find out what they chose.
				if (isset($_POST['delete']) 
					&& $this->userHasPermission($this->user,'Event Delete',$this->calendar)) {
					// User has chosen to delete the event selected, and has permission to delete from pending.
					$a_event->delete();
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
		        if (isset($_POST['pending'])
					&& $this->userHasPermission($this->user,'Event Send Event to Pending Queue',$this->calendar)) {
					$a_event->status = 'pending';
					$a_event->source = $source;
					$a_event->insert();
				} elseif (isset($_POST['posted'])
					&& $this->userHasPermission($this->user,'Event Post',$this->calendar)) {
					$a_event->status = 'posted';
					$a_event->source = $source;
					$a_event->insert();
				}
		    }
		}
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
					if ($user->find()) {
					    $user->fetch();
					} else {
					    $user = $this->createUser($this->account,$uid,$this->user);
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
			$form->setDefaults(array('calendar_id'=>$calendar->id,'account_id'=>$this->account->id));
			$renderer =& new HTML_QuickForm_Renderer_Tableless();
			$form->accept($renderer);
			if ($form->validate()) {
				$form->process(array(&$fb, 'processForm'), false);
				$form->freeze();
				$form->removeElement('__submit__');
				$msg = '<p>User permissions saved...</p>';
			}
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
	function showEventListing($status='pending',$orderby='eventdatetime.starttime')
	{
	    $mdb2 = $this->getDatabaseConnection();
	    switch($orderby) {
	        default:
	        case 'eventdatetime.starttime':
	        case 'starttime':
	            $orderby = 'eventdatetime.starttime';
	        break;
	        case 'title':
	        case 'event.title':
	            $orderby = 'event.title';
            break;
	    }
	    $sql = 'SELECT DISTINCT event.id FROM calendar_has_event, eventdatetime, event 
					WHERE calendar_has_event.status = \''.$status.'\' 
					AND calendar_has_event.event_id = event.id
					AND eventdatetime.event_id = event.id
					AND calendar_has_event.calendar_id = '.$this->calendar->id.' 
					ORDER BY '.$orderby;
		$e = array();
		$res = $mdb2->query($sql);
		if (PEAR::isError($res)) {
		    return new UNL_UCBCN_Error($res->getMessage());
		}
		if ($res->numRows()) {
			$listing = new UNL_UCBCN_EventListing();
			$listing->status = $status;
			while ($row = $res->fetchRow()) {
				$event = $this->factory('event');
				if ($event->get($row[0])) {
					if (isset($_POST['event'.$event->id])) {
					    $this->processPostStatusChange($event);
					} else {
						$listing->events[] = $event;
					}
				}
			}
			$e[] = $listing;
		} else {
			$e[] = '<p>Sorry, there are no '.$status.' events.</p><p>Perhaps you would like to create some?<br />Use the <a href="?action=createEvent">Create Event interface.</a></p>';
		}
		
		
		
		array_unshift($e, '<ul class="eventsbystatus '.$status.'">' .
							'<li id="pending_manager"><a href="'.$this->uri.'?list=pending">Pending ('.$this->getEventCount($this->calendar,'pending').')</a></li>' .
							'<li id="posted_manager"><a href="'.$this->uri.'?list=posted">Posted ('.$this->getEventCount($this->calendar,'posted').')</a></li>' .
							'<li id="archived_manager"><a href="'.$this->uri.'?list=archived">Archived ('.$this->getEventCount($this->calendar,'archived').')</a></li>' .
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
	 	if (isset($this->calendar) && $this->userHasPermission($this->user,'Calendar Edit',$this->calendar)) {
	 		$fb = DB_DataObject_FormBuilder::create($this->calendar);
			$form = $fb->getForm($this->uri.'?action=calendar&calendar_id='.$this->calendar->id);
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
	 		return array('<p>You do not have permission to edit the calendar info.</p>',$this->showChooseCalendar());
	 	}
	 }
	
	/**
	 * This function returns a list of calendars for the current account.
	 */
	function showCalendars()
	{
		$calendars = $this->factory('calendar');
		$calendars->account_id = $this->account->id;
		$calendars->orderBy('name');
		if ($calendars->find()) {
			$l = array('<ul>');
			while ($calendars->fetch()) {
				$li = $calendars->name;
				if ($this->userHasPermission($this->user,'Calendar Edit',$this->calendar)) {
					$li .= '&nbsp;<a href="'.$this->uri.'?action=calendar&amp;calendar_id='.$calendars->id.'">Edit</a>';
				}
				$l[] = '<li>'.$li.'</li>';
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
		if ($this->userHasPermission($this->user,'Calendar Change User Permissions',$this->calendar)) {
			$permissions_list = array('<ul>');
			$user_has_permission = $this->factory('user_has_permission');
			$user_has_permission->calendar_id = $this->calendar->id;
			$users = $this->factory('user');
			$users->groupBy('uid');
			$users->joinAdd($user_has_permission);
			if ($users->find()) {
				while ($users->fetch()) {
					if ($this->userHasPermission($this->user,'Calendar Change User Permissions',$this->calendar)) {
				        $user_li = '<li>'.$users->uid.'&nbsp;<a href="?action=permissions&amp;uid='.$users->uid.'">Edit Permissions</a>';
				        if ($this->userHasPermission($this->user,'Calendar Delete User',$this->calendar)) {
				            // This user can delete calendar users.
				            if (isset($_GET['remove']) 
						        && isset($_GET['uid']) 
						        && ($_GET['uid']==$users->uid)) {
						            // The user has clicked the remove user.
						            if ($users->uid != $this->user->uid) {
								        $this->calendar->removeUser($users);
								        $user_li = '<li>'.$users->uid.' (DELETED)';
						            } else {
						                $li .= 'ERROR, you cannot delete yourself!';
						            }
						    } else {
								$user_li .= '&nbsp;<a href="?action=calendar&amp;uid='.$users->uid.'&amp;remove=true">Remove User</a>';
						    }
						}
						$user_li .= '</li>';
					} else {
						$user_li = '<li>'.$users->uid.'</li>';
					}
					$permissions_list[] = $user_li;
				}
			}
			$permissions_list[] = '</ul>';
			return implode("\n",$permissions_list);
		} else {
			return new UNL_UCBCN_Error('You do not have permission to Change User Permissions for this calendar.');
		}
	}
	
	function showAddUserForm()
	{
	    unset($_GET['action']);
	    $form = new HTML_QuickForm('add_user','get');
	    //$form->addElement('text','name','User Name');
	    //$form->addElement('hidden','uid');
	    $form->addElement('text','uid','User Id (like jdoe2):');
	    $form->addElement('submit','submit','Add User');
	    $form->addElement('hidden','action','permissions');
	    return $form->toHtml();
	}

	/**
	 * This function returns all the calendars this user has access to.
	 * 
	 * @return html form for choosing account
	 */
	function showChooseCalendar()
	{
		$db = UNL_UCBCN::getDatabaseConnection();
		$res =& $db->query('SELECT u.calendar_id, c.name FROM user_has_permission AS u, calendar AS c WHERE 
					u.user_uid=\''.$this->user->uid.'\' AND 
					u.calendar_id = c.id 
					GROUP BY u.calendar_id ORDER BY c.name');
		if (PEAR::isError($res)) {
			return new UNL_UCBCN_Error($res->getMessage());
		}
		if ($res->numRows()>1) {
			$output = '<p>Calendar you are currently managing:</p>';
			$form = new HTML_QuickForm('cal_choose','get');
			$cal_select = HTML_QuickForm::createElement('select','calendar_id','');
			while ($row = $res->fetchRow()) {
				$cal_select->addOption($row[1],$row[0]);
			}
			$form->addElement($cal_select);
			$form->addElement('submit','submit','Go');
			$form->setDefaults(array('calendar_id'=>$_SESSION['calendar_id']));
			$renderer =& new HTML_QuickForm_Renderer_Tableless();
			$form->accept($renderer);
			$output .= $renderer->toHtml();
		} else {
			// User has no other calendars to manage.
			$output = '';
		}
		return $output;
	}
	
	/**
	 * Registers a plugin for use within the manager.
	 */
	function registerPlugin($class_name)
	{
		global $_UNL_UCBCN;
		if (array_key_exists('plugins',$_UNL_UCBCN) && is_array($_UNL_UCBCN['plugins'])) {
			$_UNL_UCBCN['plugins'][] = $class_name;
		} else {
			$_UNL_UCBCN['plugins'] = array($class_name);
		}
	}
}