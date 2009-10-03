<?php
/**
 * OpenID MAC support classes.
 *
 * @package OpenID
 */
/***/

require_once('Hash.php');
require_once('IllegalArgumentException.php');

/**
 * Implements the MAC as defined by OpenID.
 *
 * @package OpenID
 */
class OpenID_MAC
{
    /**
     * Does a: H($data) XOR $key
     *
     * @param string $type the name of the Hash function to use
     * @param string $data the data to sign
     * @param string $key the key we use to sign the hash of $data
     * @return string the MAC of $data
     * @exception IllegalArgumentException
     * @see Hash
     */
    static function run($type, $data, $key)
    {
        if ($key === null)
            throw new IllegalArgumentException('key cannot be null');

        $hash = Hash::run($type, $data);
        $hashLength = strlen($hash);
        if ($hashLength != strlen($key))
            throw new IllegalArgumentException('Mitchmatch between HASH and key size');

        $mac = '';
        for ($i = 0; $i < $hashLength; ++$i)
            $mac .= $hash[$i] ^ $key[$i];
        return $mac;
    }
}
?>
