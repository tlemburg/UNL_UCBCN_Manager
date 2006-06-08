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
require_once 'DB/DataObject/FormBuilder.php';
require_once 'HTML/QuickForm.php';
require_once 'Auth.php';
// Custom quickform renderer.
require_once 'UNL/UCBCN/Manager/Tableless.php';

class UNL_UCBCN_Manager extends UNL_UCBCN {

	/** Auth object */
	var $a;
	/** Account */
	var $account;
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
			$this->user = $this->getUser($this->a->getUsername());
			$this->account = $this->getAccount($this->user->uid);
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
		$fb = DB_DataObject_FormBuilder::create($events);
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
					case $this->userHasPermission($this->user,'Event Post',$this->account):
						$this->addAccountHasEvent($this->account,$events,'posted',$this->user);
					break;
					case $this->userHasPermission($this->user,'Event Send Event to Pending Queue',$this->account):
						$this->addAccountHasEvent($this->account,$events,'pending',$this->user);
					break;
					default:
						return UNL_UCBCN_Error('Sorry, you do not have permission to post an event, or send an event to the Calendar.');
				}
			}
			$form->freeze();
			$form->removeElement('__submit__');
		}
		return $renderer->toHtml();
	}
	
	/**
	 * Adds an event to an account.
	 * 
	 * @param object Account, UNL_UCBCN_Account object.
	 * @param object UNL_UCBCN_Event object.
	 * @param sring status=[pending|posted|archived]
	 * @param object UNL_UCBCN_User object
	 * 
	 * @return object UNL_UCBCN_Account_has_event
	 */
	function addAccountHasEvent($account,$event,$status,$user)
	{
		$values = array(
						'account_id'	=> $account->id,
						'event_id'		=> $event->id,
						'uid_created'	=> $user->uid,
						'date_last_updated'	=> date('Y-m-d H:i:s'),
						'uid_last_updated'	=> $user->uid,
						'status'		=> 'pending');
		return $this->dbInsert('account_has_event',$values);
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
					if ($this->userHasPermission($this->user,'Event Create',$this->account)) {
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
					$this->output = '<ul>' .
										'<li><a href="?list=pending">Pending</a></li>' .
										'<li><a href="?list=posted">Posted</a></li>' .
										'<li><a href="?list=archived">Archived</a></li>' .
									'</ul>';
					switch ($_GET['list']) {
						case 'pending':
						case 'posted':
						case 'archived':
							$this->sectitle = ucfirst($_GET['list']).' Events';
							$this->output .= $this->showEventListing($_GET['list']);
						break;
						default:
							$this->output .= $this->showEventListing('pending');
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
	 * @return string HTML snippet of events currently in the system.
	 */
	function showEventListing($status='pending')
	{
		$e = '';
		$a_event = $this->factory('account_has_event');
		$a_event->status = $status;
		$a_event->account_id = $this->account->id;
		if ($a_event->find()) {
			$oddrow = false;
			$e .= '<form action="?list='.$status.'" method="post">';
			$e .= '<table>';
			$e .= '<thead>' .
					'<tr>' .
					'<th scope="col" class="select">Select</th>' .
					'<th scope="col" class="date">Date</th>' .
					'<th scope="col" class="title">Event Title</th>' .
					'<th scope="col" class="edit">Edit</th>' .
					'</tr>' .
					'</thead>' .
					'<tbody>';
			while ($a_event->fetch()) {
				$event = $a_event->getLink('event_id');
				if (isset($_POST['event'][$event->id]) 
					&& isset($_POST['delete']) 
					&& $this->userHasPermission($this->user,'Event Remove from Pending',$this->account)) {
					// User has chosen to delete the event selected, and has permission to delete from pending.
						$a_event->delete();
				} else {
					$e .= '<tr';
					if ($oddrow) {
						$e .= ' class="alt"';
					}
					$e .= '>';
					$oddrow = !$oddrow;
					$e .=	'<td class="select"><input type="checkbox" name="event['.$event->id.']" />' .
							'<td class="date">'.$event->startdate.'</td>' .
							'<td class="title">'.$event->title.'</td>' .
							'<td class="edit"><a href="?action=createEvent&amp;id='.$event->id.'">Edit</a></td>' .
							'</tr>';
				}
			}
			$e .= '</tbody></table>';
			$e .= '<input type="submit" name="delete" value="Delete" />';
			$e .= '<input type="submit" name="post" value="Add to Posted" />';
			$e .= '</form>';
		} else {
			$e .= '<p>Sorry, there are no '.$status.' events.</p><p>Perhaps you would like to create some?<br />Use the <a href="?action=createEvent">Create Event interface.</a></p>';
		}
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
	
	/**
	 * This function returns a object for the user with
	 * the given uid.
	 * If a record does not exist, one is inserted then returned.
	 * 
	 * @param string $uid The unique user identifier for the user you wish to get (username/ldap uid).
	 * @return object UNL_UCBCN_User
	 */
	function getUser($uid)
	{
		$user = $this->factory('user');
		$user->uid = $uid;
		if ($user->find()) {
			$user->fetch();
			return $user;
		} else {
			return $this->createUser($uid,$uid);
		}
	}
	
	/**
	 * creates a new user record and returns it.
	 * @param string uid unique id of the user to create
	 * @param string optional unique id of the user who created this user.
	 */
	function createUser($uid,$uidcreated=NULL)
	{
		$values = array(
			'uid' 				=> $uid,
			'datecreated'		=> date('Y-m-d H:i:s'),
			'uidcreated'		=> $uidcreated,
			'datelastupdated' 	=> date('Y-m-d H:i:s'),
			'uidlastupdated'	=> $uidcreated);
		return $this->dbInsert('user',$values);
	}
	
	/**
	 * Gets the account record(s) that the given user has permission to.
	 * 
	 * @param string $uid User id to get an account from.
	 */
	function getAccount($uid)
	{
		$account = $this->factory('account');
		$user_has_permission = $this->factory('user_has_permission');
		$user_has_permission->user_uid = $uid;
		$account->linkAdd($user_has_permission);
		if ($account->find() && $account->fetch()) {
			return $account;
		} else {
			// No account exists!
			$values = array(
						'name'				=> ucfirst($this->user->uid).'\'s Calendar!',
						'shortname'			=> $this->user->uid,
						'uidcreated'		=> $this->user->uid,
						'uidlastupdated'	=> $this->user->uid);
			$account = $this->createAccount($values);
			$permissions = $this->factory('permission');
			$permissions->whereAdd('name LIKE "Event%"');
			if ($permissions->find()) {
				while ($permissions->fetch()) {
					$this->addPermission($uid,$account->id,$permissions->id);
				}
			}
			//$account->user_has_permission_user_id		 = $user_has_permission->user_uid;
			$account->user_has_permission_permission_id = $user_has_permission->id;
			$account->update();
			// Account has been created, but has no details, send the user to the edit account page?
			$this->localRedirect('?action=account&new=true');
		}
	}
	
	/**
	 * Checks if a user has a given permission over the account.
	 * 
	 * @param object UNL_UCBCN_User
	 * @param string permission
	 * @param object UNL_UCBCN_Account
	 * @return bool true or false
	 */
	 function userHasPermission($user,$permission,$account)
	 {
	 	$permission				= $this->factory('permission');
	 	$permission->name		= $permission;
	 	$user_has_permission	= $this->factory('user_has_permission');
	 	$user_has_permission->linkAdd($permission);
	 	$user_has_permission->linkAdd($account);
	 	$user_has_permission->user_uid = $user->uid;
	 	return $user_has_permission->find();
	 }

	/**
	 * This function adds the given permission for the user.
	 * 
	 * @param string $uid Username to add permission for.
	 * @param int $account_id ID of the account to add permission for.
	 * @param int $permission_id ID of the permission you wish to add for the person.
	 */
	function addPermission($uid,$account_id,$permission_id)
	{
		$values = array(
						'account_id'	=> $account_id,
						'user_uid'		=> $uid,
						'permission_id'=>	$permission_id
						);
		return $this->dbInsert('user_has_permission',$values);
	}

	/**
	 * This function creates a calendar account.
	 * 
	 * @param array $values assoc array of field values for the account.
	 */
	function createAccount($values = array())
	{
		if (isset($this->user)) {
			$user_has_permission = $this->factory('user_has_permission');
			$user_has_permission->user_uid = $this->user->uid;
			// I'm confused here... is user_has_permission created first or not..?
			$defaults = array(
					'datecreated'		=> date('Y-m-d H:i:s'),
					'datelastupdated'	=> date('Y-m-d H:i:s'),
					'uidlastupdated'	=> 'system',
					'uidcreated'		=> 'system');
			$values = array_merge($defaults,$values);
			return $this->dbInsert('account',$values);
		} else {
			return new UNL_UCBCN_Error('Error, could not create account.');
		}
	}
	
	/**
	 * This function is a general insert function,
	 * given the table name and an assoc array of values, 
	 * it will return the inserted record.
	 * 
	 * @param string $table Name of the table
	 * @param array $values assoc array of values to insert.
	 * @return object on success, failed return value on failure.
	 */
	function dbInsert($table,$values)
	{
		$rec = $this->factory($table);
		$vars = get_object_vars($rec);
		foreach ($values as $var=>$value) {
			if (in_array($var,$vars)) {
				$rec->$var = $value;
			}
		}
		$result = $rec->insert();
		if (!$result) {
			return $result;
		} else {
			return $rec;
		}
	}
	
	/**
	 * Redirects to the given full or partial URL.
	 * will turn the given url into an absolute url
	 * using the above getURL() function. This function
	 * does not return.
	 *
	 * @param string $url Full/partial url to redirect to
	 * @param  bool  $keepProtocol Whether to keep the current protocol or to force HTTP
	 */
	function localRedirect($url, $keepProtocol = true)
	{
		$url = self::getURL($url, $keepProtocol);
		if  ($keepProtocol == false) {
			$url = preg_replace("/^https/", "http", $url);
		}
		header('Location: ' . $url);
		exit;
	}
	
	/**
	 * Returns an absolute URL using Net_URL
	 *
	 * @param  string $url All/part of a url
	 * @return string      Full url
	 */
	function getURL($url)
	{
		include_once 'Net/URL.php';
		$obj = new Net_URL($url);
		return $obj->getURL();
	}
}