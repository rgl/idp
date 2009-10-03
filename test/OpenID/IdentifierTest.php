<?php
require_once(dirname(__FILE__).'/../global.php');
require_once('OpenID/Identifier.php');

class OpenID_IdentifierTest extends MyTestCase
{
    function testValidate()
    {
        # nothing should be throw
        OpenID_Identifier::validate("http://one.example");
        OpenID_Identifier::validate("http://one.example/?x=x");
    }

    # TODO do further tests!
}
?>