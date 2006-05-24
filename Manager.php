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
		return	'<ul>' .
				'<li><a href="?">Pending Events</a></li>'.
				'<li><a href="?action=createEvent">Create Event</a></li>'.
				'<li><a href="?action=import">Import</a></li>'.
				'<li><a href="?logout=true">LogOut</a></li>'.
				'</ul>';
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
		if ($this->a->checkAuth()) {
			$this->navigation = $this->showNavigation();
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
		} else {
			// User is not logged in.
			$this->output = $this->showLoginForm();
		}
	}
}