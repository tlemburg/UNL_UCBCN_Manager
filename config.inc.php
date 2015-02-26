<?php 

/************
 * 
 * Create a config.inc.php file in this directory if you wish to use the manager independently of the frontend config.
 * 
 ************/

/*Display errors*/
ini_set('display_errors', false);
error_reporting(E_NONE);

/*Add a custom include path here if needed....*/
set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__).'/vendor/php',
    dirname(dirname(__FILE__)).'/includes/backend',
    dirname(dirname(__FILE__)).'/includes/manager',
    dirname(dirname(__FILE__)).'/includes/frontend',
    dirname(dirname(__FILE__)).'/includes/facebook')));
    
require_once 'UNL/UCBCN/Autoload.php';

/*Global Settings*/
$dsn                 = "mysqli://root:root@localhost/events";     //default = "{{DSN}}"
$default_calendar_id = 1;             //default = 1

/*Auth setup.*/
require_once 'UNL/Auth.php';
$auth = UNL_Auth::PEARFactory('CAS');

/*Manager Settings*/
$manager_config                        = array();
$manager_config['dsn']                 = $dsn;                  //default = $dsn
$manager_config['template']            = 'unl';             //default = 'vanilla'
$manager_config['frontenduri']         = 'http://localhost:8047/';                 //default = '../'
$manager_config['a']                   = $auth;                 //default = $auth
$manager_config['default_calendar_id'] = $default_calendar_id;  //default = $default_calendar_id

//DB_DataObject::debugLevel(5);
