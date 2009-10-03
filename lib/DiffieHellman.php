<?php
/**
 * Utilities to work with the Diffie-Hellman key exchange protocol.
 *
 * @package default
 */
/***/

require_once 'BigInteger.php';
require_once 'Random.php';
require_once 'IllegalArgumentException.php';

/**
 * Diffie-Hellman key exchange protocol primitives.
 *
 * @link ftp://ftp.rsasecurity.com/pub/pkcs/ascii/pkcs-3.asc PKCS #3: Diffie-Hellman Key Agreement Standard
 * @link http://tools.ietf.org/html/rfc2631 Diffie-Hellman Key Agreement Method
 * @link http://www.rsa.com/rsalabs/node.asp?id=2248 What is Diffie-Hellman?
 * @link http://en.wikipedia.org/wiki/Diffie_hellman Diffie-Hellman key exchange
 * @package default
 */
class DiffieHellman {
    /**
     * Generates a Diffie-Hellman (DH) public and private key based on
     * the given arguments.
     *
     * @param number|string $g DH g parameter
     * @param number|string $p DH p parameter
     * @exception IllegalArgumentException
     * @return array(string, string) Generated public and private keys
     */
    static function generateKeys($g, $p) {
        if (!$g)
            throw new IllegalArgumentException('g cannot be empty or null');
        if (!$p)
            throw new IllegalArgumentException('p cannot be empty or null');
        $p = BigInteger::from($p);
        if ($p->mod(2)->cmp(0) == 0)
            throw new IllegalArgumentException('p cannot be even');

        # private key: x = random(100 digits number)
        # TODO generate a decimal: 0 < x < p-1
        $x = Random::decimal(100);
        # public key: y = g^x mod p
        $y = BigInteger::from($g)->powmod($x, $p)->toString();

        return array($y, $x);
    }

    /**
     * Computes the DiffieHellman (DH) shared secret based on the given
     * arguments.
     *
     * @param string DH $ya Other partie public key
     * @param string DH $xb Our private key
     * @param string DH $p DH p parameter
     * @exception IllegalArgumentException
     * @return string Computed DH shared secret shared between parties
     */
    static function sharedSecret($ya, $xb, $p) {
        # make sure 0 < ya < p-1
        if (bccomp($ya, 0) <= 0 or bccomp($ya, bcsub($p, 1)) >= 0)
            throw new IllegalArgumentException('ya does not satisfy 0 < ya < p-1');
        if (!$p)
            throw new IllegalArgumentException('p cannot be empty or null');
        $p = BigInteger::from($p);
        if ($p->mod(2)->cmp(0) == 0)
            throw new IllegalArgumentException('p cannot be even');
        if (!$xb)
            throw new IllegalArgumentException('xb cannot be empty');

        # shared secret: zz = ya^xb mod p
        $zz = BigInteger::from($ya)->powmod($xb, $p)->toString();

        return $zz;
    }
}
?>
