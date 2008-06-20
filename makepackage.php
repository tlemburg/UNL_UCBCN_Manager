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
$pfm = PEAR_PackageFileManager2::importOptions('package.xml', array(
//$pfm = new PEAR_PackageFileManager2();
//$pfm->setOptions(array(
    'packagedirectory'  => dirname(__FILE__),
    'baseinstalldir'    => 'UNL/UCBCN',
    'filelistgenerator' => 'svn',
    'ignore' => array(  'package.xml',
                        '.project',
                        '*.tgz',
                        'makepackage.php',
                        '*CVS/*',
                        '*.sh',
                        '*.svg',
                        '.cache',
                        'install.sh',
                        'InDesignExport.php',
                        'idexport_makepackage.php',
                        '*tests*'),
    'simpleoutput' => true,
    'roles'=>array('php'=>'data'),
    'exceptions'=>array('UNL_UCBCN_Manager_setup.php'    => 'php',
                        'Manager.php'                    => 'php',
                        'Manager/Login.php'              => 'php',
                        'Manager/FormBuilder_Driver.php' => 'php',
                        'Manager/SubForm.php'            => 'php',
                        'Manager/Tableless.php'          => 'php',
                        'Manager/FormBuilder.php'        => 'php',
                        'Manager/Recommend.php'          => 'php',
                        'Manager/Plugin.php'             => 'php',
                        'Manager/EventForm.php'          => 'php',
                        'Manager/jscalendar.php'         => 'php'
                        )
));
$pfm->setPackage('UNL_UCBCN_Manager');
$pfm->setPackageType('php'); // this is a PEAR-style php script package
$pfm->setSummary('A Management frontend for publishing University events.');
$pfm->setDescription('This package gives authenticated users access to publish events ' .
        'into their own calendar for their Department/Unit within a University.
        It uses PEAR Auth to connect with existing authentication sources (LDAP etc).
        It handles authentication for the user and allows ' .
                'insertion of event details into the calendar backend.');
$pfm->setChannel('pear.unl.edu');
$pfm->setAPIStability('beta');
$pfm->setReleaseStability('beta');
$pfm->setAPIVersion('0.6.0');
$pfm->setReleaseVersion('0.6.0');
$pfm->setNotes('
Feature Release:
* Users can recommend events to calendars which allow recommendations.

CSS Fix:
* Add margin to paragraphs, fixes form legend overlapping text.
');

//$pfm->addMaintainer('lead','saltybeagle','Brett Bieber','brett.bieber@gmail.com');
//$pfm->addMaintainer('developer','alvinwoon','Alvin Woon','alvinwoon@gmail.com');
$pfm->setLicense('BSD License', 'http://www1.unl.edu/wdn/wiki/Software_License');
$pfm->clearDeps();
$pfm->setPhpDep('5.0.0');
$pfm->setPearinstallerDep('1.4.3');
$pfm->addPackageDepWithChannel('required', 'DB_DataObject_FormBuilder', 'pear.php.net', '0.18.1');
$pfm->addPackageDepWithChannel('required', 'Auth', 'pear.php.net', '1.3.0');
$pfm->addPackageDepWithChannel('required', 'UNL_UCBCN', 'pear.unl.edu', '0.6.0');
$pfm->addPackageDepWithChannel('required', 'Pager', 'pear.php.net', '2.2.1');
$pfm->addPackageDepWithChannel('required', 'HTML_Table', 'pear.php.net', '1.6.0');
foreach (array('Manager.php','UNL_UCBCN_Manager_setup.php','index.php') as $file) {
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
    $task->getParam('createtemplate', 'Create/Upgrade default templates?', 'string', 'yes'),
    $task->getParam('createindex',    'Create/Upgrade sample index page?', 'string', 'yes'),
    $task->getParam('createaccount',  'Create a calendar account?', 'string', 'yes'),
    ));
$task->addParamGroup('fileSetup', array(
    $task->getParam('docroot',        'Path to root of webserver', 'string', '/Library/WebServer/Documents/events/manager'),
    $task->getParam('template',       'Template style to use', 'string', 'default')
    ));
$task->addParamGroup('accountSetup', array(
    $task->getParam('dsn',            'Database connection string (DSN)', 'string', 'mysqli://eventcal:eventcal@localhost/eventcal'),
    $task->getParam('name',           'Account Title', 'string', 'UNL Events'),
    $task->getParam('shortname',      'Account Short Name', 'string', 'unlevents')
    ));

$pfm->addPostinstallTask($task, 'UNL_UCBCN_Manager_setup.php');
$pfm->generateContents();
if (isset($_SERVER['argv']) && $_SERVER['argv'][1] == 'make') {
    $pfm->writePackageFile();
} else {
    $pfm->debugPackageFile();
}
?>