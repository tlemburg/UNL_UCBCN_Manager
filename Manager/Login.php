<?php
/**
 * This class is for the login form.
 * 
 * @package UNL_UCBCN_Manager
 * @author Brett Bieber
 */
 
class UNL_UCBCN_Manager_Login
{
	public $post_url;
	public $user_field;
	public $password_field;
	
	
	function __construct()
	{
		$this->post_url = $_SERVER['SCRIPT_FILENAME'];
		$this->user_field = 'username';
		$this->password_field = 'password';
	}
}
?>