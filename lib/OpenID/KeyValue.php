<?php
/**
 * OpenID Key-Value type classes.
 *
 * @package OpenID
 */
/***/

require_once('IllegalArgumentException.php');

/**
 * (De|En)codes the OpenID Key-Value type.
 *
 * @link http://openid.net/specs/openid-authentication-2_0-11.html#kvform
 * @package OpenID
 */
class OpenID_KeyValue
{
    /**
     * Encoded the given $data hash into a OpenID kvform.
     *
     * @param hash $data data to encode
     * @param array $order order of the generated fields in kvform format;  when null, the fields might not be ordered
     * @return string the kvform of the given $data
     * @exception IllegalArgumentException when a key or value is invalid
     */
    static function encode($data, $order=null)
    {
        if (!$order)
        	$order = array_keys($data);
        $encoded = '';
        $count = count($order);
        for ($n = 0; $n < $count; ++$n) {
            $key = $order[$n];
            $value = $data[$key];
            self::validateKeyValue($key, $value);
            $encoded .= "$key:$value\n";
        }
        return $encoded;
    }

    /**
     * Decodes the given OpendID kvform $encoded into a hash.
     *
     * @param string $encoded encoded data to dencode
     * @return hash the decoded data
     * @exception IllegalArgumentException when a key or value is invalid
     */
    static function decode($encoded)
    {
        $all = array();
        $lines = explode("\n", $encoded);
        # make sure the last line is empty (because there must be a \n
        # in every line)
        if (array_pop($lines) !== '')
            throw new IllegalArgumentException('Illegal encoded KeyValue');
        foreach ($lines as $line) {
            $kv = explode(':', $line, 2);
            if (count($kv) != 2)
                throw new IllegalArgumentException('Illegal encoded line');
            list($key, $value) = $kv;
            self::validateKeyValue($key, $value);
            if (array_key_exists($key, $all))
                throw new IllegalArgumentException('Cannot contain repeated Keys');
            $all[$key] = $value;
        }
        return $all;
    }

    /**
     * Validates the key and value.
     *
     * @param string $key key to validate
     * @param string $value value to validate
     * @exception IllegalArgumentException when a key or value is invalid
     */
    private static function validateKeyValue($key, $value)
    {
        # NB: We are positive filtering instead of negative because
        #     thats the way the OpenID spec defines these KeyValues.
        # A Key cannot contain colons or whitespace.
        if (preg_match('/[:\\s]/', $key))
            throw new IllegalArgumentException('Invalid Key');
        # A Value cannot begin or end with white-spaces and cannot
        # contain newlines.  A Value can be empty.
        if (preg_match('/(^\\s+)|\\n|(\\s+$)/', $value))
            throw new IllegalArgumentException('Invalid Value');
    }
}
?>
