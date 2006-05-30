<?php

/**
 * This file instantiates the Event manager interface.
 */
require_once 'UNL/UCBCN/Manager.php';
require_once 'Auth.php';
$a = new Auth('DB',array('dsn'=>'mysql://eventcal:eventcal@localhost/eventcal'),NULL,false);

$frontend = new UNL_UCBCN_Manager(array(	'template'=>'default',
											'dsn'=>'mysql://eventcal:eventcal@localhost/eventcal',
											'a'=>$a));
$frontend->run();
UNL_UCBCN::displayRegion($frontend);

?>