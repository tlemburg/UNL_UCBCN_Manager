<?php
require_once 'Testing/Selenium.php';
require_once 'PHPUnit/Framework/TestCase.php';

class UNL_UCBCN_Manager_EventActionsTest extends PHPUnit_Framework_TestCase
{
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main() {
        require_once 'PHPUnit/TextUI/TestRunner.php';

        $suite  = new PHPUnit_Framework_TestSuite(__CLASS__);
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    function setUp()
    {
        $this->verificationErrors = array();
        $this->selenium = new Testing_Selenium("*firefox", "http://localhost/");
        $result = $this->selenium->start();
    }

    function tearDown()
    {
        $this->selenium->stop();
    }
    
    function testResetDatabase()
    {
        $mysqlbin = '/usr/local/mysql/bin/mysql';
        $res = system($mysqlbin.' -u eventcal --password=eventcal eventcal < '.dirname(__FILE__).'/EventActionsTest.sql');
        $this->assertEquals('', $res);
    }

    function testManagerActions()
    {
        $this->selenium->open("/events/manager/");
        $this->selenium->type("username", "ppeters1");
        $this->selenium->type("password", "1234");
        $this->selenium->click("submit");
        $this->selenium->waitForPageToLoad("30000");
        try {
            $this->assertTrue($this->selenium->isTextPresent("Pending (406)"));
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            array_push($this->verificationErrors, $e->toString());
        }
        $this->selenium->click("link=Check All");
        $this->selenium->select("document.formlist.action[0]", "label=Select action...");
        
        // Delete all elements.
        $this->selenium->select("document.formlist.action[0]", "label=Delete");
        $this->selenium->waitForPageToLoad("30000");
        $this->assertTrue((bool)preg_match('/^Are you sure[\s\S]$/',$this->selenium->getConfirmation()));
        try {
            $this->assertTrue($this->selenium->isTextPresent("Pending (376)"));
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            array_push($this->verificationErrors, $e->toString());
        }
        $this->selenium->click("link=6");
        $this->selenium->waitForPageToLoad("30000");
        $this->selenium->click("link=Check All");
        $this->selenium->select("document.formlist.action[0]", "label=Select action...");
        // Delete all elements.
        $this->selenium->select("document.formlist.action[0]", "label=Delete");
        $this->selenium->waitForPageToLoad("30000");
        $this->assertTrue((bool)preg_match('/^Are you sure[\s\S]$/',$this->selenium->getConfirmation()));
        try {
            $this->assertTrue($this->selenium->isTextPresent("Pending (346)"));
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            array_push($this->verificationErrors, $e->toString());
        }
        $this->selenium->click("link=Check All");
        $this->selenium->select("document.formlist.action[0]", "label=Add to Posted");
        $this->selenium->waitForPageToLoad("30000");
        try {
            $this->assertTrue($this->selenium->isTextPresent("Pending (316)"));
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            array_push($this->verificationErrors, $e->toString());
        }
        $this->selenium->click("link=Archived (21)");
        $this->selenium->waitForPageToLoad("30000");
        $this->selenium->click("link=Check All");
        $this->selenium->select("document.formlist.action[0]", "label=Move to Pending");
        $this->selenium->waitForPageToLoad("30000");
        try {
            $this->assertTrue($this->selenium->isTextPresent("Archived (0)"));
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            array_push($this->verificationErrors, $e->toString());
        }
        $this->selenium->click("link=Posted (9)");
        $this->selenium->waitForPageToLoad("30000");
        $this->selenium->click("link=Check All");
        $this->selenium->select("document.formlist.action[0]", "label=Move to Pending");
        $this->selenium->waitForPageToLoad("30000");
        try {
            $this->assertTrue($this->selenium->isTextPresent("Posted (0)"));
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            array_push($this->verificationErrors, $e->toString());
        }
        $this->selenium->click("link=Pending (346)");
        $this->selenium->waitForPageToLoad("30000");
        $this->selenium->type("searchinput", "hannukah");
        $this->selenium->click("document.event_search.submit");
        $this->selenium->waitForPageToLoad("30000");
        $this->selenium->type("searchinput", "presidents day");
        $this->selenium->click("document.event_search.submit");
        $this->selenium->waitForPageToLoad("30000");
        $this->selenium->click("//td[2]");
        $this->selenium->select("document.formlist.action[1]", "label=Add to Posted");
        $this->selenium->waitForPageToLoad("30000");
        $this->selenium->click("link=Pending Events");
        $this->selenium->waitForPageToLoad("30000");
        try {
            $this->assertTrue($this->selenium->isTextPresent("Posted (1)"));
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            array_push($this->verificationErrors, $e->toString());
        }
        $this->selenium->click("link=LogOut");
        $this->selenium->waitForPageToLoad("30000");
        try {
            $this->assertEquals("UNL Event Publishing System | Event Manager Login", $this->selenium->getTitle());
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            array_push($this->verificationErrors, $e->toString());
        }
    }
}

?>