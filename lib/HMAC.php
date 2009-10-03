<?php
/**
 * Utilities to convert decimal numbers.
 *
 * @package default
 */
/***/

require_once('Random.php');
require_once('Hash.php');
require_once('IllegalArgumentException.php');

/**
 * Generates cryptographic hash signatures to authenticate data.
 *
 * @link http://tools.ietf.org/html/rfc2104 HMAC: Keyed-Hashing for Message Authentication
 * @package default
 */
class HMAC
{
    /**
     * Factory method to create a new HMAC instance.
     *
     * When the key length is higher the hash P block size, the key is
     * hashed before use.  Unfortunatelly, there is no way to known the
     * P block size (which is the actual block size in which the
     * algorithm operates) using the PHP exported functionality, which,
     * in this current class implementation, we are using mhash, and
     * that extension does not export the mhash_get_hash_pblock
     * function...
     *
     * @param string $type name of the HASH to use. eg: SHA1
     * @param string $key the binary key.  Can be of any length or null to
     * automatically generate a random key.
     * @see Hash
     */
    static function create($type, $key=null)
    {
        if ($key === null) {
            # RCF2104 advices the use of a Key that has a length (B) of
            # the size of the intrinsic hash algorithm and a minimum key
            # that is equal to the hash algorithm output byte-length (L).
            # When the Key length < B, it will be zero padded.
            # We use the recommended size (B).
            $keySize = Hash::blockSize($type);
            $key = Random::bytes($keySize);
        }
        $hashId = Hash::_hashId($type);
        return new HMAC($type, $hashId, $key);
    }

    /**
     * Initializes the instance.
     *
     * @param string $type name of the HASH to use. eg: SHA1
     * @param string $hashId hash to use
     * @param string $key binary key
     */
    private function __construct($type, $hashId, $key)
    {
        $this->type = $type;
        $this->hashId = $hashId;
        $this->key = $key;
    }

    /**
     * @return string key used to sign data
     */
    function key()
    {
    	return $this->key;
    }

    /**
     * Signs the given $data.
     *
     * @param string $data data to sign
     * @return string signature of data
     * @exception IllegalArgumentException
     */
    function sign($data)
    {
        $signature = mhash($this->hashId, $data, $this->key);
        if ($signature == FALSE)
            throw new IllegalArgumentException('Failed to sign');
        return $signature;
    }
}
?>
