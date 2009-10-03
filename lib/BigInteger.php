<?php
/**
 * Classes for working with Big Integers.
 *
 * @package default
 */
/***/

require_once('Base64.php');
require_once('IllegalArgumentException.php');

/**
 * Provides a common access interface to a Big Integer library like BCMath or GMP.
 */
interface BigIntegerProvider
{
    /**
     * Initializes a BigInteger.
     *
     * @param string|integer $number integer to initialize
     * @return mixed object that represents the $number as a big integer
     */
    function init($number);

    /**
     * Converts the given number to an string.
     *
     * @param string|integer $a number to convert
     * @return string $a as a string
     */
    function toString($a);

    /**
     * Adds the given numbers.
     *
     * @param string|integer $a number to add
     * @param string|integer $b number to add
     * @return string sum of arguments as $a + $b
     */
    function add($a, $b);

    /**
     * Subtracts the given numbers.
     *
     * @param string|integer $a number to subtract
     * @param string|integer $b number to subtract
     * @return string subtraction of the arguments as $a - $b
     */
    function sub($a, $b);

    /**
     * Divides the given numbers.
     *
     * @param string|integer $a number to divide
     * @param string|integer $b number to divide
     * @return string subtraction of the arguments as $a / $b
     */
    function div($a, $b);

    /**
     * Multiplies the given numbers.
     *
     * @param string|integer $a number to multiply
     * @param string|integer $b number to multiply
     * @return string subtraction of the arguments as $a * $b
     */
    function mul($a, $b);

    /**
     * Calulates $n modulo $d.
     *
     * @param string|integer $n
     * @param string|integer $d
     * @return string result of $n modulo $d
     */
    function mod($n, $d);

    /**
     * Computes the formula: $base ^ $exp modulo $mod
     *
     * @param string|integer $base the base number
     * @param string|integer $exp the positive power to raise the base
     * @param string|integer $mod the modulo
     * @return string the result of formula $base ^ $exp modulo $mod
     */
    function powmod($base, $exp, $mod);

    /**
     * Compares the given numbers.
     *
     * @param string|integer $a number to compare
     * @param string|integer $b number to compare
     * @return integer positive value when a > b, 0 when a = b, negative value when a < b
     */
    function cmp($a, $b);
}

/**
 * A BigIntegerProvider that uses the BCMath library.
 */ 
class BigIntegerBCMathProvider implements BigIntegerProvider
{
    function init($number) {
        return $number;
    }

    function toString($a) {
        return $a;
    }

    function add($a, $b) {
        return bcadd($a, $b);
    }

    function sub($a, $b) {
        return bcsub($a, $b);
    }

    function div($a, $b) {
        return bcdiv($a, $b);
    }

    function mul($a, $b) {
        return bcmul($a, $b);
    }

    function mod($a, $b) {
        return bcmod($a, $b);
    }

    function powmod($base, $exp, $mod) {
        return bcpowmod($base, $exp, $mod);
    }

    function cmp($a, $b) {
        return bccomp($a, $b);
    }
}

/**
 * A BigIntegerProvider that uses the GMP library.
 */ 
class BigIntegerGMPProvider implements BigIntegerProvider
{
    function init($n) {
        return gmp_init($n);
    }

    function toString($a) {
        return gmp_strval($a);
    }

    function add($a, $b) {
        return gmp_add($a, $b);
    }

    function sub($a, $b) {
        return gmp_sub($a, $b);
    }

    function div($a, $b) {
        return gmp_div($a, $b);
    }

    function mul($a, $b) {
        return gmp_mul($a, $b);
    }

    function mod($a, $b) {
        return gmp_mod($a, $b);
    }

    function powmod($base, $exp, $mod) {
        return gmp_powm($base, $exp, $mod);
    }

    function cmp($a, $b) {
        return gmp_cmp($a, $b);
    }
}

/**
 * Represents a Big Integer.
 *
 * Under the hood this uses the GMP (if available) or the BCMath library.
 *
 * @package default
 */
class BigInteger {
    public static $provider;

    /**
     * The number as represented by the provider class.
     *
     * @var mixed
     */
    private $n;

    private function __construct($n) {
        $this->n = $n;
    }

    /**
     * Creates a new BigInteger from the given number.
     *
     * @param string|integer $number
     * @return BigInteger the BigInteger that represents the number
     */
    static function from($number) {
        return new BigInteger(self::cast($number));
    }

    private static function cast($number) {
        return $number instanceof BigInteger ? $number->n : self::$provider->init($number);
    }

    /**
     * Converts this number to a string.
     *
     * @return string
     */
    function toString() {
        return self::$provider->toString($this->n);  
    }

    /**
     * Adds this number with the given number
     *
     * @param BigInteger $b number to add
     * @return BigInteger a new number with the sum of $this and $b
     */
    function add($b) {
        return new BigInteger(self::$provider->add($this->n, self::cast($b)));
    }

    /**
     * Subtracts the given number from this number.
     *
     * @param BigInteger|string|integer $b number to subtract
     * @return BigInteger subtraction of $this - $b
     */
    function sub($b) {
        return new BigInteger(self::$provider->sub($this->n, self::cast($b)));
    }

    /**
     * Divides this number with the given number.
     *
     * @param BigInteger|string|integer $b
     * @return BigInteger result of $a / $b
     */
    function div($b) {
        return new BigInteger(self::$provider->div($this->n, self::cast($b)));
    }

    /**
     * Multiplies this with the given number.
     *
     * @param BigInteger|string|integer $b number to multiply
     * @return BigInteger result of $this - $b
     */
    function mul($b) {
        return new BigInteger(self::$provider->mul($this->n, self::cast($b)));
    }

    /**
     * Calulates $this modulo $d.
     *
     * @param BigInteger|string|integer $d
     * @return BigInteger result of $this modulo $d
     */
    function mod($d) {
        return new BigInteger(self::$provider->mod($this->n, self::cast($d)));
    }

    /**
     * Computes the formula: $this ^ $exp modulo $mod
     *
     * @param BigInteger|string|integer $exp the positive power to raise $this
     * @param BigInteger|string|integer $mod the modulo
     * @return string the result of formula $this ^ $exp modulo $mod
     */
    function powmod($exp, $mod) {
        return new BigInteger(self::$provider->powmod($this->n, self::cast($exp), self::cast($mod)));
    }

    /**
     * Compares this number with the given number.
     *
     * @param BigInteger|string|integer $b number to compare to
     * @return integer positive value when $this > $b, 0 when $this = $b, negative value when $this < $b
     */
    function cmp($b) {
        return self::$provider->cmp($this->n, self::cast($b));
    }
}

# if the user has the GMP library, then use it.  otherwise fallback to the slower BCMath.
BigInteger::$provider = function_exists('gmp_init') ? new BigIntegerGMPProvider() : new BigIntegerBCMathProvider();
?>
