--TEST--
newRecordInserted
--SKIPIF--
<?php require_once dirname(__FILE__).'/config.php'; ?>
--FILE--
<?php
$_POST = $_REQUEST = array('title' => 'New movie', 'genre_id' => '5', '_qf__dataobject_movie' => '');
include(dirname(__FILE__).'/config.php');
$do2 =& DB_DataObject::factory('movie');
if (PEAR::isError($do2)) {
    die($do2->getMessage());
}
if ($do2->get('title', $_POST['title'])) {
	die('Found before insert
');
}
$do =& DB_DataObject::factory('movie');
if (PEAR::isError($do)) {
    die($do->getMessage());
}
$fb =& DB_DataObject_FormBuilder::create($do);
if (PEAR::isError($fb)) {
    die($fb->getMessage());
}
$form =& $fb->getForm();
if ($form->validate()) {
	echo 'Validated
';
	$form->process(array(&$fb, 'processForm'), false);
	$do2 =& DB_DataObject::factory('movie');
	if ($do2->get('title', $_POST['title'])) {
		echo 'Found
';
		$do2->delete();
	}
} else {
	$form->display();
}
?>
--EXPECT--
Validated
Found
