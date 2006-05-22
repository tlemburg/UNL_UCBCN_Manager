<?php

/**
 * This file instantiates the Event manager interface.
 */
require_once 'UNL/UCBCN/Manager.php';

$frontend = new UNL_UCBCN_Manager(array(	'template'=>'default',
											'dsn'=>'mysql://eventcal:eventcal@localhost/eventcal'));
$frontend->run();
UNL_UCBCN::displayRegion($frontend);

?>