<?php
require_once(dirname(__FILE__).'/../global.php');
require_once('OpenID/Association.php');

class OpenID_AssociationTest extends MyTestCase
{
    function testSignVerify()
    {
        $a = OpenID_Association::create('HMAC-SHA1');
        $this->assertEquals(false, $a->stateless());

        $fields = array('a', 'b');
        $data = array('a'=>'A', 'b'=>'B');
        $signature = $a->sign($fields, $data);
        $this->assertEquals(Hash::outputSize('SHA1'), strlen($signature));
        $this->assertTrue($a->verify($fields, $data, $signature));
    }
}
?>