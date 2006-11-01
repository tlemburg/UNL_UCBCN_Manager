<?php
/**
 * This class is for the login form.
 * 
 * @package UNL_UCBCN_Manager
 * @author Brett Bieber
 */
 
/**
 * Simple object which will be used to display a login.
 * 
 * @package UNL_UCBCN_Manager
 */
class UNL_UCBCN_Manager_Login
{
    /**
     * URL to post the form to.
     *
     * @var string
     */
	public $post_url;
	
	/**
	 * Name of the form field for the user.
	 *
	 * @var string
	 */
	public $user_field;
	
	/**
	 * Name of the form field for the password
	 *
	 * @var string
	 */
	public $password_field;
	
	function __construct()
	{
		$this->post_url = $_SERVER['SCRIPT_FILENAME'];
		$this->user_field = 'username';
		$this->password_field = 'password';
	}
}
?>