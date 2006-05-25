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
	/** Navigation */
	var $navigation;
	/** Account on right column */
	var $accountright;
	/** Unique body ID */
	var $uniquebody;
	/** Main content of the page sent to the client. */
	var $output;
	
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
				'<p>Welcome, Tom Hanks</p>'."\n".
				'<ul>'."\n".
				'<li><a href="#">Account Info</a></li>'."\n".
				'<li><a href="?logout=true">LogOut</a></li>'."\n".
				'<li><a href="#">Help</a></li>'."\n".
				'</ul>'."\n".
				'</div>';
	}
	
	/**
	 * Returns unique BODY tag ID
	 * 
	 * @return ID.
	 */
	function showBodyID()
	{
		$url_path = $_SERVER["REQUEST_URI"];
			switch (TRUE){
				case (eregi("/*.*/?action=createEvent/*.*",$url_path)):
				return 'id="create"';
				break;
				
				case (eregi("/*.*/?action=import/*.*",$url_path)):
				return 'id="import"';
				break;
				
				case (eregi("/*.*/?action=search/*.*",$url_path)):
				return 'id="search"';
				break;
				
				case (eregi("/*.*/?action=subscribe/*.*",$url_path)):
				return 'id="subscribe"';
				break;
				
				default:
				return 'id="normal"';
				break;
				}
	}
	
	/**
	 * Returns a HTML form for the user to authenticate with.
	 * 
	 * @return html form for authenticating the user.
	 */
	function showLoginForm()
	{
		$form = new HTML_QuickForm('login');
		$form->addElement('text','username','User');
		$form->addElement('password','password','Password');
		$form->addElement('submit','submit','Submit');
		return $form->toHtml();
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
		//if ($this->a->checkAuth()) {
			$this->navigation = $this->showNavigation();
			$this->accountright = $this->showAccountRight();
			$this->uniquebody = $this->showBodyID();
			// User is authenticated.
			if (empty($action) && isset($_GET['action'])) {
				$action = $_GET['action'];
			}
			switch($action)
			{
				case 'createEvent':
					$this->output = $this->showEventSubmitForm();
				break;
				case 'import':
					$this->output = $this->showImportForm();
				break;
				default:
					$this->output = '<p>List of pending events.</p>';
				break;
			}
		//} else {
			// User is not logged in.
		//	$this->output = $this->showLoginForm();
		//}
	}
}