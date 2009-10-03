<?php
require_once(dirname(__FILE__).'/../../global.php');
require_once('OpenID/SR/TimeZone.php');

class OpenID_SR_TimeZoneTest extends MyTestCase
{
    function testExists()
    {
        $result = OpenID_SR_TimeZone::fromCode('Europe/Lisbon');
        $this->assertNotEquals(null, $result );
        $this->assertEquals('Europe/Lisbon', $result->code());
    }
}
?>