<?php
/**
 * Base64 utilities.
 *
 * @package default
 */
/***/

require_once('IllegalArgumentException.php');

/**
 * Encode and decode data between binary and ASCII.
 *
 * The encoded string is composed solely of printable ASCII
 * characters from the alphabet A-Za-z0-9+/ and 0 to 2 trailing
 * padding characters =.
 *
 * We need this class, because unfortunately, PHP base64_decode does
 * not check its argument for an invalid base64 string.
 *
 * @package default
 * @link http://tools.ietf.org/html/rfc4648 The Base16, Base32, and Base64 Data Encodings
 */
class Base64 {
    /**
     * Encodes the given $binary data into a Base64 encoded string.
     *
     * @param string $binary Data to encode
     * @exception IllegalArgumentException
     * @return string Base64 encoded data
     */
    static function encode($binary) {
        if ($binary === null)
            throw new IllegalArgumentException('binary cannot be null');
        if ($binary == '')
            throw new IllegalArgumentException('binary cannot be empty');
        return base64_encode($binary);
    }

    /**
     * Decodes the given Base64 encoded string into a binary string.
     *
     * @param string $base64 Data to decode
     * @exception IllegalArgumentException
     * @return string Decoded binary data
     */
    static function decode($base64) {
        if (!preg_match('/^[A-Za-z\\d+\\/]+={0,2}$/', $base64))
            throw new IllegalArgumentException('base64 is not valid');
        if (strlen($base64) % 4 != 0)
            throw new IllegalArgumentException('base64 is not multiple of 4');
        $binary = base64_decode($base64);
        # NB: This check for false is here, but the actual base64_decode
        #     does not return it (at least in PHP 5.2.1).
        if ($binary === false)
            throw new IllegalArgumentException('base64 failed to decode');
        return $binary;
    }
}
?>
