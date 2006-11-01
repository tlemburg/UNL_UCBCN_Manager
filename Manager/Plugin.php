<?php
/**
 * This is a base class manager plugins must extend and implement.
 * 
 * @package UNL_UCBCN_Manager
 */

require_once 'UNL/UCBCN/Manager.php';

/**
 * Abstract class plugins must extend and implement.
 *
 * @package UNL_UCBCN_Manager
 */
abstract class UNL_UCBCN_Manager_Plugin
{
	var $name;
	var $version;
	var $author;
	
	var $manager;
	var $uri;
	
	abstract function startup(&$manager,$uri);
}
?>