<?php
/**
 * This file instantiates the Event manager interface.
 *
 * PHP version 5
 *
 * @category  Events
 * @package   UNL_UCBCN_Manager
 * @author    Brett Bieber <brett.bieber@gmail.com>
 * @copyright 2009 Regents of the University of Nebraska
 * @license   http://www1.unl.edu/wdn/wiki/Software_License BSD License
 * @link      http://code.google.com/p/unl-event-publisher/
 */

//load the config in this dir, otherwise load the frontend config.
$manager_config = dirname(__FILE__).'/../config.inc.php';
require_once $manager_config;

set_time_limit(120);

/*Auth setup.*/
require_once 'UNL/Auth.php';
$auth = UNL_Auth::PEARFactory('CAS');

$manager_config['a']                   = $auth;                 //default = $auth

$manager = new UNL_UCBCN_Manager($manager_config);

$manager->run();

UNL_UCBCN::displayRegion($manager);

