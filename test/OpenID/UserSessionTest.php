<?php
require_once(dirname(__FILE__).'/../global.php');
require_once('OpenID/UserSession.php');

class OpenID_UserSessionTest extends MyTestCase
{
    function setUp()
    {
        $this->session = OpenID_UserSession::create();
    }

    function tearDown()
    {
        $this->session->destroy();
        $this->session = null;
    }

    function testCreate()
    {
        $this->assertEquals(0, count($this->session->all()));
        $this->assertEquals(
            true,
            array_key_exists(
                OpenID_UserSession::PHP_SESSION_KEY, $_SESSION
            )
        );
    }

    function testOpen()
    {
	$_SESSION['user'] = 'test';
        $this->session->set('key', 'value');
        $s1 = OpenID_UserSession::open();
	unset($_SESSION['user']);
        $s1->set('key2', 'value2');
        $this->assertEquals(array('key', 'key2'), $this->session->all());
        $this->assertEquals(array('key', 'key2'), $s1->all());
    }

    function testSet()
    {
        $this->session->set('key', 'value');
        $this->assertEquals('value', $this->session->get('key'));
        $this->assertEquals(array('key'), $this->session->all());
    }

    function testDestroy()
    {
        $this->session->destroy();
        $this->assertEquals(
            false,
            array_key_exists(
                OpenID_UserSession::PHP_SESSION_KEY, $_SESSION
            )
        );
    }
}
?>
