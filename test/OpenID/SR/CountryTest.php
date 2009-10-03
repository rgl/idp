<?php
require_once(dirname(__FILE__).'/../../global.php');
require_once('OpenID/SR/Country.php');

class OpenID_SR_CountryTest extends MyTestCase
{
    function testExists()
    {
        $c = OpenID_SR_Country::fromCode('PT');
        $this->assertNotEquals(null, $c);
        $this->assertEquals('PT', $c->code());
        $this->assertEquals('Portugal', $c->name());
    }

    function testAssociatedTimeZones()
    {
        $c = OpenID_SR_Country::fromCode('PT');
        $this->assertNotEquals(null, $c);
        $expected = array(
            'Europe/Lisbon',
            'Atlantic/Madeira',
            'Atlantic/Azores'
        );
        $zones = $c->timeZones();
        $this->assertNotEquals(null, $zones);
        $result = array();
        foreach ($zones as $zone)
            $result[] = $zone->code();
        $this->assertEquals($expected, $result);
    }
}
?>