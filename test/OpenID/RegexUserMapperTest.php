<?php
require_once(dirname(__FILE__).'/../global.php');
require_once('OpenID/RegexUserMapper.php');

class OpenID_RegexUserMapperTest extends MyTestCase
{
    public function setup()
    {
        OpenID_Config::_push();
        OpenID_Config::set('idp.identity.url', 'http://localhost/id');
        OpenID_Config::set('user.mapper.class', 'OpenID_RegexUserMapper');
        $this->mapper = new OpenID_RegexUserMapper();
    }

    public function tearDown()
    {
        OpenID_Config::_pop();
    }

    public function testUserToIdentity()
    {
        OpenID_Config::set('RegexUserMapper.userToIdentity', array(
            array('/(.*)@(.*)/', '$2/$1'),
        ));
        $this->assertEquals(
            'http://localhost/id/example.com/test',
            $this->mapper->userToIdentity('test@example.com')
        );
    }

    public function testIdentityToUser()
    {
        OpenID_Config::set('RegexUserMapper.identityToUser', array(
            array('/(.*)\\/(.*)/', '$2@$1'),
        ));
        $this->assertEquals(
            'test@example.com',
            $this->mapper->identityToUser('http://localhost/id/example.com/test')
        );
    }

    public function testUserToIdentityWithEmptyMap()
    {
        OpenID_Config::set('RegexUserMapper.userToIdentity', array());
        $this->assertEquals(null, $this->mapper->userToIdentity('test@example.com'));
    }

    public function testIdentityToUserWithEmptyMap()
    {
        OpenID_Config::set('RegexUserMapper.identityToUser', array());
        $this->assertEquals(null, $this->mapper->identityToUser('http://localhost/id/example.com/test'));
    }

    public function testUserToIdentityWithNullMap()
    {
        OpenID_Config::set('RegexUserMapper.userToIdentity', null);
        $this->assertEquals(null, $this->mapper->userToIdentity('test@example.com'));
    }

    public function testIdentityToUserWithNullMap()
    {
        OpenID_Config::set('RegexUserMapper.identityToUser', null);
        $this->assertEquals(null, $this->mapper->identityToUser('http://localhost/id/example.com/test'));
    }

    public function testUserToIdentityWithNonMatchingUser()
    {
        OpenID_Config::set('RegexUserMapper.userToIdentity', array(
            array('/(.*)@(.*)/', '$2/$1'),
        ));
        $this->assertEquals(null, $this->mapper->userToIdentity('example.com'));
    }

    public function testIdentityToUserWithNonMatchingUser()
    {
        OpenID_Config::set('RegexUserMapper.identityToUser', array(
            array('/(.*)\/(.*)/', '$2@$1'),
        ));
        $this->assertEquals(null, $this->mapper->identityToUser('http://localhost/id/example.com'));
    }

    public function testUserToIdentityFirstMatchWins()
    {
        OpenID_Config::set('RegexUserMapper.userToIdentity', array(
            array('/xpto@example\.com/', 'zero'),
            array('/(.*)@example\.com/', 'one'),
            array('/(.*)@(.*)/', 'two'),
        ));
        $this->assertEquals(
            'http://localhost/id/one',
            $this->mapper->userToIdentity('one@example.com')
        );
    }

    public function testIdentityToUserFirstMatchWins()
    {
        OpenID_Config::set('RegexUserMapper.identityToUser', array(
            array('/zero/', 'zero@example.com'),
            array('/one/',  'one@example.com'),
            array('/(.*)/', 'two@example.com'),
        ));
        $this->assertEquals(
            'one@example.com',
            $this->mapper->identityToUser('http://localhost/id/one')
        );
    }

    public function testIdentityToUserWithInvalidBaseDomain()
    {
        OpenID_Config::set('RegexUserMapper.identityToUser', array(
            array('/example\.com/(.*)/', '$1'),
        ));
        $this->assertEquals(null, $this->mapper->identityToUser('http://example.net/id/example.com/test'));
    }
}
?>
