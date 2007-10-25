<?php
require_once 'Testing/Selenium.php';
require_once 'PHPUnit/Framework/TestCase.php';

class UNL_UCBCN_Manager_CreateEventTest extends PHPUnit_Framework_TestCase
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

    function testCreateEventCase()
    {
        $this->selenium->open("/events/manager/");
        $this->selenium->type("username", "test");
        $this->selenium->type("password", "test");
        $this->selenium->click("submit");
        $this->selenium->waitForPageToLoad("30000");
        $this->selenium->click("link=Create Event");
        $this->selenium->waitForPageToLoad("30000");
        $this->selenium->type("document.unl_ucbcn_event.title", "This is a test event.");
        $this->selenium->select("__reverseLink_event_has_eventtype_event_ideventtype_id_1", "label=Exhibit - Photography");
        $this->selenium->select("__reverseLink_eventdatetime_event_idlocation_id_1", "label=AGRICULTURAL HALL");
        $this->selenium->click("date1_calendar_trigger");
        $this->selenium->click("__submit__");
        $this->selenium->waitForPageToLoad("30000");
        try {
            $this->assertTrue($this->selenium->isTextPresent("This is a test event."));
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            array_push($this->verificationErrors, $e->toString());
        }
        $this->selenium->click("link=LogOut");
        $this->selenium->waitForPageToLoad("30000");

    }
}

?>