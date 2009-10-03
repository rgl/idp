<?php
require_once(dirname(__FILE__).'/../global.php');
require_once('OpenID/MAC.php');

class OpenID_MACTest extends MyTestCase
{
    function testSHA1()
    {
        # http://tools.ietf.org/html/rfc3174
        # the key is the SHA1 of 'abc'.  Which, after applying the
        # OpenID MAC: SHA1('abc') XOR key SHOULD be 0.
        $key = "\xA9\x99\x3E\x36\x47\x06\x81\x6A\xBA\x3E\x25\x71\x78\x50\xC2\x6C\x9C\xD0\xD8\x9D";
        $this->assertEquals(
            str_repeat("\x00", 20),
            OpenID_MAC::run('SHA1', 'abc', $key)
        );
    }

    function testSHA256()
    {
        # http://tools.ietf.org/html/rfc3174
        # the key is the SHA1 of 'abc'.  Which, after applying the
        # OpenID MAC: SHA1('abc') XOR key SHOULD be 0.
        $key = "\xba\x78\x16\xbf\x8f\x01\xcf\xea\x41\x41\x40\xde\x5d\xae\x22\x23\xb0\x03\x61\xa3\x96\x17\x7a\x9c\xb4\x10\xff\x61\xf2\x00\x15\xad";
        $this->assertEquals(
            str_repeat("\x00", 32),
            OpenID_MAC::run('SHA256', 'abc', $key)
        );
    }
}
?>