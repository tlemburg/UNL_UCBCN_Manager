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
	'baseinstalldir' => 'UNL/UCBCN/Manager/Plugins',
	'filelistgenerator' => 'file',
	'ignore' => array(	'package.xml',
						'.project',
						'*.tgz',
						'makepackage.php',
						'*CVS/*',
						'.cache',
						'idexport_makepackage.php'),
	'simpleoutput' => true,
	'roles'=>array('php'=>'php'	),
	'exceptions'=>array(
						)
));
$pfm->setPackage('UNL_UCBCN_Manager_InDesignExport');
$pfm->setPackageType('php'); // this is a PEAR-style php script package
$pfm->setSummary('Export a range of events to Adobe InDesign Tags.');
$pfm->setDescription('This package is a small plugin for the UNL_UCBCN_Manager which ' .
		'allows event publishers to export calendar data into Adobe InDesign tags format.');
$pfm->setChannel('pear.unl.edu');
$pfm->setAPIStability('alpha');
$pfm->setReleaseStability('alpha');
$pfm->setAPIVersion('0.0.2');
$pfm->setReleaseVersion('0.0.2');
$pfm->setNotes("* Add subtitle");

$pfm->addMaintainer('lead','saltybeagle','Brett Bieber','brett.bieber@gmail.com');
$pfm->setLicense('PHP License', 'http://www.php.net/license');
$pfm->clearDeps();
$pfm->setPhpDep('5.0.0');
$pfm->setPearinstallerDep('1.4.3');
$pfm->addPackageDepWithChannel('required', 'UNL_UCBCN', 'pear.unl.edu', '0.3.0');


$pfm->generateContents();
if (isset($_SERVER['argv']) && $_SERVER['argv'][1] == 'make') {
    $pfm->writePackageFile();
} else {
    $pfm->debugPackageFile();
}
?>