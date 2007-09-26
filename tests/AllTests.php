<?php
/**
 * Test suite for UNL_UCBCN_Manager
 *
 * PHP versions 5
 *
 * @category Events
 * @package  UNL_UCBCN_Manager
 * @author   Brett Bieber <brett.bieber@gmail.com>
 */

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'UNL_UCBCN_Manager_AllTests::main');
}

require_once 'PHPUnit/Framework/TestSuite.php';
require_once 'PHPUnit/TextUI/TestRunner.php';

require_once 'UNL/UCBCN/ManagerTest.php';

class UNL_UCBCN_Manager_AllTests
{
	/**
     * Runs the test suite.
     *
     * @return unknown
     */
    public static function main()
    {

        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    /**
     * Adds the UNL_UCBCN_ManagerTest suite.
     *
     * @return $suite
     */
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('UNL_UCBCN_Manager tests');
        /** Add testsuites, if there is. */
        $suite->addTestSuite('UNL_UCBCN_ManagerTest');


        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'UNL_UCBCN_Manager_AllTests::main') {
    UNL_UCBCN_Manager_AllTests::main();
}
?>