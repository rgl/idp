<?php
require_once('global.php');
require_once('BigInteger.php');

class BigIntegerTest extends MyTestCase
{
    public function testToString()
    {
        $number = '123456789012345678901234567890';
        $this->assertEquals($number, BigInteger::from($number)->toString());
    }

    public function testAdd()
    {
        $a = BigInteger::from('9999999999999999999999999999999999');
        $expected =          '10000000000000000000000000000000000';
        $this->assertEquals($expected, $a->add(1)->toString());
    }

    public function testSub()
    {
        $a = BigInteger::from('10000000000000000000000000000000000');
        $expected = '9999999999999999999999999999999999';
        $this->assertEquals($expected, $a->sub(1)->toString());
    }

    public function testMul()
    {
        $a = BigInteger::from('1234567891234');
        $expected = '12345678912340';
        $this->assertEquals($expected, $a->mul(10)->toString());
    }

    public function testDiv()
    {
        $a = BigInteger::from('12345678912340');
        $expected = '1234567891234';
        $this->assertEquals($expected, $a->div(10)->toString());
    }

    public function testMod()
    {
        $a = BigInteger::from('12345678912341');
        $expected = '1';
        $this->assertEquals($expected, $a->mod(10)->toString());
    }

    public function testPowmod()
    {
        $base = BigInteger::from('12345077');
        $this->assertEquals('77', $base->powmod(1, 1000)->toString());

        $base = BigInteger::from('12345077');
        $this->assertEquals('409', $base->powmod('1234', 1000)->toString());
    }

    public function testCmp()
    {
        $n0 = BigInteger::from('10000000000000000000000000000000000');
        $n1 = BigInteger::from('10000000000000000000000000000000001');
        $this->assertTrue($n0->cmp($n0) == 0);
        $this->assertTrue($n1->cmp($n1) == 0);
        $this->assertTrue($n0->cmp($n1) < 0);
        $this->assertTrue($n1->cmp($n0) > 0);
    }
}
?>
