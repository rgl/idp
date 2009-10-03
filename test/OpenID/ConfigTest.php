<?php
require_once(dirname(__FILE__).'/../global.php');

class OpenID_ConfigTest extends MyTestCase
{
    public function setup()
    {
        OpenID_Config::_push();
    }

    public function tearDown()
    {
    	OpenID_Config::_pop();
    }

    public function testExpandedWithTwoExpansions()
    {
        OpenID_Config::set('db.prefix', 'PREFIX_');
        OpenID_Config::set('profile', 'PROFILE');
        OpenID_Config::set('db.dsn', 'mysql:host=localhost;dbname={db.prefix}{profile}');
        $this->assertEquals(
            'mysql:host=localhost;dbname=PREFIX_PROFILE',
            OpenID_Config::expanded('db.dsn')
        );
    }

    public function testExpandedWithEscape()
    {
        OpenID_Config::set('r', 'R');
        OpenID_Config::set('x', '{{{r}');
        $this->assertEquals(
            '{R',
            OpenID_Config::expanded('x')
        );
    }

    public function testDeduceProfile()
    {
        $_REQUEST['X-APP_PROFILE'] = 'production';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        OpenID_Config::set('profile', 'test');
        OpenID_Config::set('profile.deduce', 'X-APP_PROFILE');
        OpenID_Config::set('profile.deduce.allow_from', '127.0.0.1');
        $this->assertEquals(
            'production',
            OpenID_Config::profile()
        );
    }

    # profile must belong to the test, development, production set.
    # an invalid profile is ignored, and the 'test' profile is
    # used (because its the one that can be trashed...).
    public function testDeduceWithInvalidProfile()
    {
        $_REQUEST['X-APP_PROFILE'] = 'INVALID_PROFILE';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        OpenID_Config::set('profile', 'test');
        OpenID_Config::set('profile.deduce', 'X-APP_PROFILE');
        OpenID_Config::set('profile.deduce.allow_from', '127.0.0.1');
        $this->assertEquals(
            'test',
            OpenID_Config::profile()
        );
    }

    # profile cannot be deduced because the request is not from localhost.
    public function testCannotDeduceProfile()
    {
        $_REQUEST['X-APP_PROFILE'] = 'production';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.2';
        OpenID_Config::set('profile', 'test');
        OpenID_Config::set('profile.deduce', 'X-APP_PROFILE');
        OpenID_Config::set('profile.deduce.allow_from', '127.0.0.1');
        $this->assertEquals(
            'test',
            OpenID_Config::profile()
        );
    }
}
?>