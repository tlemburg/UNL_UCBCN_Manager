<?php
/**
 * This is a base class manager plugins must extend and implement.
 */

require_once 'UNL/UCBCN/Manager.php';

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