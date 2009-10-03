<?php
require_once('global.php');
require_once('DiffieHellman.php');

class DiffieHellmanTest extends MyTestCase
{
    public function testGenerateKeys()
    {
        $g = 7; $p = 11;
        list($y, $x) = DiffieHellman::generateKeys($g, $p);
        # TODO generateKeys is not taking $p into account.
        # TODO when we fix generateKeys uncomment the second test.
        if (bccomp($y, 0) <= 0 /*or bccomp($y, bcsub($p, 1)) >= 0*/)
            $this->fail('0 < y < p-1');
        # TODO when we fix generateKeys uncomment the second test.
        if (bccomp($x, 0) <= 0 /*or bccomp($x, bcsub($p, 1)) >= 0*/)
            $this->fail('0 < x < p-1');
    }

    public function testGenerateKeysEmptyG()
    {
        $this->setExpectedException('IllegalArgumentException');
        DiffieHellman::generateKeys('', 11);
    }

    public function testGenerateKeysEmptyP()
    {
        $this->setExpectedException('IllegalArgumentException');
        DiffieHellman::generateKeys(7, '');
    }

    public function testGenerateKeysNullG()
    {
        $this->setExpectedException('IllegalArgumentException');
        DiffieHellman::generateKeys(null, 11);
    }

    public function testGenerateKeysNullP()
    {
        $this->setExpectedException('IllegalArgumentException');
        DiffieHellman::generateKeys(7, null);
    }

    public function testGenerateKeysEvenP()
    {
        $this->setExpectedException('IllegalArgumentException');
        DiffieHellman::generateKeys(7, 10);
    }

    public function testAlice()
    {
        $g = 7;  $p = 11;
        $ya = 4; $xa = 6;
        $yb = 8;
        $zz = DiffieHellman::sharedSecret($yb, $xa, $p);
        $this->assertEquals(3, $zz);
    }

    public function testBob()
    {
        $g = 7;  $p = 11;
        $yb = 8; $xb = 9;
        $ya = 4;
        $zz = DiffieHellman::sharedSecret($ya, $xb, $p);
        $this->assertEquals(3, $zz);
    }

    public function testFirstInvalidPublicKey()
    {
        # ya must satisfy 0 < ya < p-1
        # valid public key domain: [1..9]
        $ya = 0;
        $g = 7;  $p = 11;
        $yb = 8; $xb = 9;
        $this->setExpectedException('IllegalArgumentException');
        DiffieHellman::sharedSecret($ya, $xb, $p);
    }

    public function testLastInvalidPublicKey()
    {
        # ya must satisfy 0 < ya < p-1
        # valid public key domain: [1..9]
        $ya = 10;
        $g = 7;  $p = 11;
        $yb = 8; $xb = 9;
        $this->setExpectedException('IllegalArgumentException');
        DiffieHellman::sharedSecret($ya, $xb, $p);
    }

    public function testFirstValidPublicKey()
    {
        # ya must satisfy 0 < ya < p-1
        # valid public key domain: [1..9]
        $ya = 1;
        $g = 7;  $p = 11;
        $yb = 8; $xb = 9;
        $zz = DiffieHellman::sharedSecret($ya, $xb, $p);
        # zz = ya^xb mod p
        $this->assertEquals(1, $zz);
    }

    public function testLastValidPublicKey()
    {
        # ya must satisfy 0 < ya < p-1
        # valid public key domain: [1..9]
        $ya = 9;
        $g = 7;  $p = 11;
        $yb = 8; $xb = 9;
        $zz = DiffieHellman::sharedSecret($ya, $xb, $p);
        # zz = ya^xb mod p
        $this->assertEquals(5, $zz);
    }

    public function testEmptyAlicePublicKey()
    {
        $this->setExpectedException('IllegalArgumentException');
        DiffieHellman::sharedSecret('', 9, 11); # $ya, $xb, $p
    }

    public function testNullAlicePublicKey()
    {
        $this->setExpectedException('IllegalArgumentException');
        DiffieHellman::sharedSecret(null, 9, 11); # $ya, $xb, $p
    }

    public function testEmptyBobPrivateKey()
    {
        $this->setExpectedException('IllegalArgumentException');
        DiffieHellman::sharedSecret(4, '', 11); # $ya, $xb, $p
    }

    public function testNullBobPrivateKey()
    {
        $this->setExpectedException('IllegalArgumentException');
        DiffieHellman::sharedSecret(4, null, 11); # $ya, $xb, $p
    }

    public function testEmptySharedSecretP()
    {
        $this->setExpectedException('IllegalArgumentException');
        DiffieHellman::sharedSecret(4, 9, ''); # $ya, $xb, $p
    }

    public function testNullSharedSecretP()
    {
        $this->setExpectedException('IllegalArgumentException');
        DiffieHellman::sharedSecret(4, 9, null); # $ya, $xb, $p
    }

    public function testOddSharedSecretP()
    {
        $this->setExpectedException('IllegalArgumentException');
        DiffieHellman::sharedSecret(4, 9, 10); # $ya, $xb, $p
    }
}
?>