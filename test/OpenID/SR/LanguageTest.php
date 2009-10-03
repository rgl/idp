<?php
require_once(dirname(__FILE__).'/../../global.php');
require_once('OpenID/SR/Language.php');

class OpenID_SR_LanguageTest extends MyTestCase
{
    function testExists()
    {
        $c = OpenID_SR_Language::fromCode('pt');
        $this->assertNotEquals(null, $c);
        $this->assertEquals('pt', $c->code());
        $this->assertEquals('Portuguese', $c->name());
    }
}
?>