<?php
require_once('global.php');
require_once('HMAC.php');

class HMACTest extends MyTestCase
{
    function testSHA1KeyGeneration()
    {
    	$hmac = HMAC::create('SHA1');
        $this->assertEquals(Hash::blockSize('SHA1'), strlen($hmac->key()));
    }

    function testSHA1()
    {
        # The tests vectors were obtained from Python 2.5, eg:
        #   hmac.new('key', 'abc', hashlib.sha1).hexdigest()
        $key = 'key';
        $hmac = HMAC::create('SHA1', $key);
        $this->assertEquals(
            "\x4f\xd0\xb2\x15\x27\x6e\xf1\x2f\x2b\x3e\x4c\x8e\xca\xc2\x81\x14\x98\xb6\x56\xfc",
            $hmac->sign('abc')
        );
    }

    function testSHA256KeyGeneration()
    {
        $hmac = HMAC::create('SHA256');
        $this->assertEquals(Hash::blockSize('SHA256'), strlen($hmac->key()));
    }

    function testSHA256()
    {
        # The tests vectors were obtained from Python 2.5, eg:
        #   hmac.new('key', 'abc', hashlib.sha256).hexdigest()
        $key = 'key';
        $hmac = HMAC::create('SHA256', $key);
        $this->assertEquals(
            "\x9c\x19\x6e\x32\xdc\x01\x75\xf8\x6f\x4b\x1c\xb8\x92\x89\xd6\x61\x9d\xe6\xbe\xe6\x99\xe4\xc3\x78\xe6\x83\x09\xed\x97\xa1\xa6\xab",
            $hmac->sign('abc')
        );
    }
}
?>