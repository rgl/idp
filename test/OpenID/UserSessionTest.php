<?php
require_once(dirname(__FILE__).'/../global.php');
require_once('OpenID/UserSession.php');

class OpenID_UserSessionTest extends MyTestCase
{
    function tearDown()
    {
    	OpenID_UserSession::destroy();
    }

    function testCreate()
    {
        $session = OpenID_UserSession::create();
        $this->assertEquals(0, count($session->all()));
        $this->assertEquals(
            true,
            array_key_exists(
                OpenID_UserSession::PHP_SESSION_KEY, $_SESSION
            )
        );
    }

    function testOpen()
    {
        $session = OpenID_UserSession::create();
        $session->set('key', 'value');
        $s1 = OpenID_UserSession::open();
        $s1->set('key2', 'value2');
        $this->assertEquals(array('key', 'key2'), $session->all());
        $this->assertEquals(array('key', 'key2'), $s1->all());
    }

    function testSet()
    {
        $session = OpenID_UserSession::create();
        $session->set('key', 'value');
        $this->assertEquals('value', $session->get('key'));
        $this->assertEquals(array('key'), $session->all());
    }

    function testDestroy()
    {
        $session = OpenID_UserSession::create();
        $session->destroy();
        $this->assertEquals(
            false,
            array_key_exists(
                OpenID_UserSession::PHP_SESSION_KEY, $_SESSION
            )
        );
    }
}
?>