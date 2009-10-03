<?php
/**
 * Utilities to generate cryptographic hashes.
 *
 * @package default
 */
/***/

require_once('IllegalArgumentException.php');

/**
 * Generates cryptography hashes of data.
 *
 * @link http://tools.ietf.org/html/rfc3174 US Secure Hash Algorithm 1 (SHA1)
 * @link http://tools.ietf.org/html/rfc4634 US Secure Hash Algorithms (SHA and HMAC-SHA)
 * @package default
 */
class Hash
{
    /**
     * @param string $type name of hash. eg: SHA1.
     * @return int ID of the hash specific to the mhas library
     * @link http://pt2.php.net/manual/en/ref.mhash.php Mhash Library
     */
    static function _hashId($type)
    {
        switch ($type) {
            case 'SHA1':
                return MHASH_SHA1;
            case 'SHA256':
                return MHASH_SHA256;
            default:
                throw new IllegalArgumentException('Unknown hash type');
        }
    }

    /**
     * Computes the hash of the given $data using the $type hash.
     *
     * @param string $type name of hash. eg: SHA1.
     * @param string $data data to hash
     * @return string hash (binary) of $data
     * @exception IllegalArgumentException
     */
    static function run($type, $data)
    {
        $hash = mhash(self::_hashId($type), $data);
        if ($hash == FALSE)
            throw new IllegalArgumentException('Failed to hash');
        return $hash;
    }

    /**
     * @param string $type name of hash. eg: SHA1.
     * @return int The input block byte-size (B) of the given hash
     *             algorithm.
     * @exception IllegalArgumentException
     */
    static function blockSize($type)
    {
        # We do not have access to mhash_get_hash_pblock function which
        # returns the block size (B);  BUT we known these sizes from
        # RFC4634 (and by confirming them on the mhash library sources),
        # so we return them here.
        switch ($type) {
            # Both SHA1 and SHA256 have L=64 (512 bits).
            case 'SHA1':
            case 'SHA256':
                return 64;
            default:
                throw new IllegalArgumentException('Unknown hash type');
        }
    }

    /**
     * @param string $type name of hash. eg: SHA1.
     * @return int The output byte-size of the given hash algorithm.
     * @exception IllegalArgumentException
     */
    static function outputSize($type)
    {
        $size = mhash_get_block_size(self::_hashId($type));
        if (FALSE === $size)
            throw new IllegalArgumentException('Unknown hash type');
        return $size;
    }
}
?>
