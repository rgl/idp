<?php
require_once('global.php');
require_once('Hash.php');

class HashTest extends MyTestCase
{
    function testSHA1()
    {
        # These tests vectors (only the first two, because there are
        # only run once) come from:
        #   http://tools.ietf.org/html/rfc3174

        $this->assertEquals(64, Hash::blockSize('SHA1'));
        $this->assertEquals(20, Hash::outputSize('SHA1'));

        # TEST1
        $this->assertEquals(
            "\xA9\x99\x3E\x36\x47\x06\x81\x6A\xBA\x3E\x25\x71\x78\x50\xC2\x6C\x9C\xD0\xD8\x9D",
            Hash::run('SHA1', 'abc')
        );
        # TEST2
        $this->assertEquals(
            "\x84\x98\x3E\x44\x1C\x3B\xD2\x6E\xBA\xAE\x4A\xA1\xF9\x51\x29\xE5\xE5\x46\x70\xF1",
            Hash::run('SHA1', 'abcdbcdecdefdefgefghfghighijhijkijkljklmklmnlmnomnopnopq')
        );
    }

    function testSHA256()
    {
        # These tests vectors here obtained from Python 2.5 hashlib, eg:
        #   hashlib.sha256('abc').hexdigest()

        $this->assertEquals(64, Hash::blockSize('SHA256'));
        $this->assertEquals(32, Hash::outputSize('SHA256'));

        $this->assertEquals(
            "\xba\x78\x16\xbf\x8f\x01\xcf\xea\x41\x41\x40\xde\x5d\xae\x22\x23\xb0\x03\x61\xa3\x96\x17\x7a\x9c\xb4\x10\xff\x61\xf2\x00\x15\xad",
            Hash::run('SHA256', 'abc')
        );
        $this->assertEquals(
            "\x24\x8d\x6a\x61\xd2\x06\x38\xb8\xe5\xc0\x26\x93\x0c\x3e\x60\x39\xa3\x3c\xe4\x59\x64\xff\x21\x67\xf6\xec\xed\xd4\x19\xdb\x06\xc1",
            Hash::run('SHA256', 'abcdbcdecdefdefgefghfghighijhijkijkljklmklmnlmnomnopnopq')
        );
    }

    function testBlockSizeWithUnknownHash()
    {
        $this->setExpectedException('IllegalArgumentException');
        Hash::outputSize('X-HASH-RUILOPES');
    }

    function testRunWithUnknownHash()
    {
        $this->setExpectedException('IllegalArgumentException');
        Hash::run('X-HASH-RUILOPES', 'data');
    }
}
?>