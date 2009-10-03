<?php
/**
 * Utilities to convert decimal numbers.
 *
 * @package default
 */
/***/

require_once('BigInteger.php');
require_once('Base64.php');
require_once('IllegalArgumentException.php');

/**
 * Converts decimal numbers between text and binary representations.
 *
 * @package default
 */
class Decimal {
    /**
     * Converts a positive decimal number to a binary big-endian
     * representation.
     *
     * @param string $decimal Decimal number to convert
     * @exception IllegalArgumentException
     * @return string Binary representation
     */
    static function toBinary($decimal) {
        # TODO see if its faster to use an array instead of building a string.
        if ($decimal == '' or $decimal === null)
            throw new IllegalArgumentException('decimal cannot be empty or null');
        $provider = BigInteger::$provider;
        if ($provider->cmp($decimal, 0) < 0)
            throw new IllegalArgumentException('decimal cannot be negative');
        $binary = '';
        do {
            $byte = chr($provider->mod($decimal, 256));
            $decimal = $provider->div($decimal, 256);
            $binary = "$byte$binary";
        } while ($provider->cmp($decimal, 0) > 0);
        # the most-significative must be lower than 127
        # because we only encode positive numbers.
        if (ord($binary[0]) > 127)
            $binary = "\x00$binary";
        return $binary;
    }

    /**
     * Converts a decimal number from its binary representation to a
     * positive decimal number.
     *
     * @param string $binary Binary decimal number to convert
     * @param string $default Decimal number to return when $binary is null
     * @exception IllegalArgumentException
     * @return string Decimal number
     */
    static function fromBinary($binary, $default=null) {
        if ($binary === null)
            return $default;
        if (!$binary)
            throw new IllegalArgumentException('binary cannot be empty');
        if (ord($binary[0]) > 127)
            throw new IllegalArgumentException('binary cannot be negative');
        $length = strlen($binary);
        $n = 0;
        for ($i = 0; $i < $length; ++$i) {
            $byte = ord($binary[$i]);
            $n = bcmul($n, 256);
            $n = bcadd($n, $byte);
        }
        return $n;
    }

    # TODO benchmark these two fromBinary*
    private static function fromBinary2($binary) {
        # NB: unpack returns an array (thats starts at 1!).
        $bytes = unpack('C*', $binary);
        $n = 0;
        foreach ($bytes as $byte) {
            $n = bcmul($n, 256);
            $n = bcadd($n, $byte);
        }
        return $n;
    }

    /**
     * Converts a decimal number from its Base64 encoded binary
     * representation to a positive decimal number.
     *
     * @param string $binary_as_base64 Binary decimal number to convert
     * @param string $default Decimal number to return when $binary_as_base64 is null
     * @exception IllegalArgumentException
     * @return string Decimal number
     * @see fromBinary()
     */
    static function fromBase64($binary_as_base64, $default=null) {
        if ($binary_as_base64 === null)
            return $default;
        $binary = Base64::decode($binary_as_base64);
        return Decimal::fromBinary($binary, $default);
    }

    /**
     * Converts a positive decimal number to a binary big-endian
     * representation Base64 encoded.
     *
     * @param string $decimal Decimal number to convert
     * @exception IllegalArgumentException
     * @return string Binary representation Base64 encoded
     * @see toBinary()
     */
    static function toBase64($decimal) {
        return Base64::encode(Decimal::toBinary($decimal));
    }
}
?>
