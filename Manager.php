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

class UNL_UCBCN_Manager extends UNL_UCBCN {

	/** Auth object */
	var $a;
	/** Account */
	var $account;
	/** User object */
	var $user;
	/** Navigation */
	var $navigation;
	/** Account on right column */
	var $accountright;
	/** Unique body ID */
	var $uniquebody;
	/** Main content of the page sent to the client. */
	var $output;
	/** Page Title */
	var $doctitle;

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
				'<li id="calendar"><a href="#" title="My Calendar">Pending Events</a></li>'."\n".
				'<li id="create"><a href="?action=createEvent" title="Create Event">Create Event</a></li>'."\n".
				'<li id="search"><a href="#" title="Search">Search</a></li>'."\n".
				'<li id="subscribe"><a href="#" title="Subscribe">Subscribe</a></li>'."\n".
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
		$form = new HTML_QuickForm('login');
		$form->addElement('header','loginhead','Event Publisher Login');
		$form->addElement('text','username','User');
		$form->addElement('password','password','Password');
		$form->addElement('submit','submit','Submit');
		return $intro.$form->toHtml();
	}
	
	/**
	 * Returns a form for entering/editing an event.
	 * 
	 * @return string HTML form for entering an event into the database.
	 */
	function showEventSubmitForm()
	{
		$events = $this->factory('event');
		$fb = DB_DataObject_FormBuilder::create($events);
		$form = $fb->getForm($_SERVER['PHP_SELF'].'?action=createEvent');
		if ($form->validate()) {
			// Form has passed the client/server validation and can be inserted.
			$form->process(array(&$fb, 'processForm'), false);
			$form->freeze();
			$form->removeElement('__submit__');
			return $form->toHtml();
		} else {
			return $form->toHtml();
		}
	}
	
	/**
	 * Returns a html form for importing xml/.ics files.
	 * 
	 * @return string HTML form for uploading a file.
	 */
	function showImportForm()
	{
		$form = new HTML_QuickForm();
		$form->addElement('header','importhead','Import iCalendar .ics/xml:');
		$form->addElement('file','filename','Filename');
		return $form->toHtml();
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
					$this->output = $this->showEventSubmitForm();
					$this->uniquebody = 'id="create"';
				break;
				case 'import':
					$this->output = $this->showImportForm();
					$this->uniquebody = 'id="import"';
				break;
				case 'search':
					$this->uniquebody = 'id="search"';
				break;
				case 'subscribe':
					$this->uniquebody = 'id="subscribe"';
				break;
				case 'account':
					$this->output = $this->showAccountForm();
				break;
				default:
					$this->output = '<p>List of pending events.</p>';
					$this->uniquebody = 'id="normal"';
				break;
			}
		} else {
			// User is not logged in.
			$this->output = $this->showLoginForm();
		}
	}
	
	/**
	 * Returns a form to edit the current acccount.
	 * @return string html form.
	 */
	function showAccountForm()
	{
		if (isset($this->account)) {
			$fb = DB_DataObject_FormBuilder::create($this->account);
			$form = $fb->getForm();
			return $form->toHtml();
		} else {
			return $this->showAccounts();
		}
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
			$form->addElement('header','accounthead','Choose an account:');
			$acc_select = HTML_QuickForm::createElement('select','account_id','Account');
			while ($user_has_permission->fetch()) {
				$acc_select->addOption($user_has_permission->account_id);
			}
			$form->addElement($acc_select);
			$form->addElement('submit','submit','Submit');
			$output .= $form->toHtml();
		} else {
			// Error, user has no permission to anything!
			$output = 'Sorry, you do not have permission to edit/access any accounts.';
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
		$user->user_uid = $uid;
		$account->linkAdd($user);
		if ($account->find() && $account->fetch()) {
			return $account;
		} else {
			return $this->createAccount($values);
		}
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
			return 'Error, could not create account.';
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
		$vars = getObjectVars($rec);
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
}