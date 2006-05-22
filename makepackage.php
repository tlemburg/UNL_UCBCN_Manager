<?php
ini_set('display_errors',true);
require_once 'PEAR/PackageFileManager2.php';
require_once 'PEAR/PackageFileManager/File.php';
require_once 'PEAR/Task/Postinstallscript/rw.php';
require_once 'PEAR/Config.php';
require_once 'PEAR/Frontend.php';

/**
 * @var PEAR_PackageFileManager
 */
PEAR::setErrorHandling(PEAR_ERROR_DIE);
chdir(dirname(__FILE__));
//$pfm = PEAR_PackageFileManager2::importOptions('package.xml', array(
$pfm = new PEAR_PackageFileManager2();
$pfm->setOptions(array(
	'packagedirectory' => dirname(__FILE__),
	'baseinstalldir' => 'UNL/UCBCN',
	'filelistgenerator' => 'file',
	'ignore' => array(	'package.xml',
						'.project',
						'*.tgz',
						'makepackage.php',
						'*CVS/*',
						'.cache'),
	'simpleoutput' => true,
	'roles'=>array('php'=>'data'	),
	'exceptions'=>array('UNL_UCBCN_Manager_setup.php'=>'php',
						'Manager.php'=>'php')
));
$pfm->setPackage('UNL_UCBCN_Manager');
$pfm->setPackageType('php'); // this is a PEAR-style php script package
$pfm->setSummary('This package provides a management frontend for events inside the UNL_UCBCN system.');
$pfm->setDescription('This class extends the UNL UCBerkeley Calendar backend system to create
			a management frontend. It handles authentication for the user and allows
			insertion of event details into the calendar backend.');
$pfm->setChannel('pear.unl.edu');
$pfm->setAPIStability('alpha');
$pfm->setReleaseStability('alpha');
$pfm->setAPIVersion('0.0.1');
$pfm->setReleaseVersion('0.0.1');
$pfm->setNotes('Initial Release... this is really bare-bones.
		* Integration with Auth
		* Create Event form.
		* Default template for the manager.');

$pfm->addMaintainer('lead','saltybeagle','Brett Bieber','brett.bieber@gmail.com');
$pfm->setLicense('PHP License', 'http://www.php.net/license');
$pfm->clearDeps();
$pfm->setPhpDep('5.0.0');
$pfm->setPearinstallerDep('1.4.3');
$pfm->addPackageDepWithChannel('required', 'DB_DataObject_FormBuilder', 'pear.php.net', '0.18.1');
$pfm->addPackageDepWithChannel('required', 'Auth', 'pear.php.net', '1.2.3');
$pfm->addPackageDepWithChannel('required', 'UNL_UCBCN', 'pear.unl.edu', '0.0.1');
foreach (array('Manager.php','dataobject.ini','UNL_UCBCN_Manager_setup.php','index.php') as $file) {
	$pfm->addReplacement($file, 'pear-config', '@PHP_BIN@', 'php_bin');
	$pfm->addReplacement($file, 'pear-config', '@PHP_DIR@', 'php_dir');
	$pfm->addReplacement($file, 'pear-config', '@DATA_DIR@', 'data_dir');
	$pfm->addReplacement($file, 'pear-config', '@DOC_DIR@', 'doc_dir');
}

$config = PEAR_Config::singleton();
$log = PEAR_Frontend::singleton();
$task = new PEAR_Task_Postinstallscript_rw($pfm, $config, $log,
    array('name' => 'UNL_UCBCN_Manager_setup.php', 'role' => 'php'));
$task->addParamGroup('questionCreate', array(
	$task->getParam('createtemplate',	'Create/Upgrade default templates?', 'string', 'yes'),
	$task->getParam('createindex',	'Create/Upgrade sample index page?', 'string', 'yes'),
	));
$task->addParamGroup('fileSetup', array(
	$task->getParam('docroot',		'Path to root of webserver', 'string', '/Library/WebServer/Documents/events/manager/'),
	$task->getParam('template',	'Template style to use', 'string', 'default')
    ));

$pfm->addPostinstallTask($task, 'UNL_UCBCN_Manager_setup.php');
$pfm->generateContents();
if (isset($_SERVER['argv']) && $_SERVER['argv'][1] == 'make') {
    $pfm->writePackageFile();
} else {
    $pfm->debugPackageFile();
}
?>