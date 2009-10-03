<?php
/**
 * Utilities do generate random data.
 *
 * @package default
 */
/**
 * Generates random data.
 *
 * @package default
 */
class Random {
    /**
     * Generates $count bytes of random data.
     *
     * @param int $count number of byte to generate
     * @return array random data
     * @todo try to use /dev/urandom, or openssl random.
     */
    static function bytes($count) {
        $bytes = '';
        while ($count-- > 0)
            $bytes .= chr(mt_rand(0, 255));
        return $bytes;
    }

    /**
     * Generates a $count digits random decimal number.
     *
     * @param int $count number of digits to generate
     * @return array random decimal number
     */
    static function decimal($count) {
        if ($count <= 0)
            return '';
        $decimal = chr(mt_rand(ord('1'), ord('9')));
        while (--$count > 0)
            $decimal .= chr(mt_rand(ord('0'), ord('9')));
        return $decimal;
    }
}
?>
