<?php
require_once('global.php');
require_once('Decimal.php');

class DecimalTest extends MyTestCase
{
    public function testFromBinary()
    {
        $this->assertEquals('273', Decimal::fromBinary("\x01\x11"));
    }

    public function testNullBinaryReturnsDefaultValue()
    {
        $this->assertEquals(9999, Decimal::fromBinary(null, 9999));
    }

    public function testEmptyBinaryThrowsException()
    {
        $this->setExpectedException('IllegalArgumentException');
        Decimal::fromBinary('');
    }

    public function testFromBinaryWithNegativeNumber()
    {
        $this->setExpectedException('IllegalArgumentException');
        Decimal::fromBinary("\xff");
    }

    public function testToBinaryAndFromBinary()
    {
        # from http://openid.net/specs/openid-authentication-2_0-11.html#btwoc
        $this->assertEquals("\x00", Decimal::toBinary('0'));
        $this->assertEquals('0',    Decimal::fromBinary("\x00"));
        $this->assertEquals("\x7F", Decimal::toBinary('127'));
        $this->assertEquals('127',  Decimal::fromBinary("\x7F"));
        $this->assertEquals("\x00\x80", Decimal::toBinary('128'));
        $this->assertEquals('128',      Decimal::fromBinary("\x00\x80"));
        $this->assertEquals("\x00\xFF", Decimal::toBinary('255'));
        $this->assertEquals('255',      Decimal::fromBinary("\x00\xFF"));
        $this->assertEquals("\x01\x11", Decimal::toBinary('273'));
        $this->assertEquals('273',      Decimal::fromBinary("\x01\x11"));
        $this->assertEquals("\x00\x80\x00", Decimal::toBinary('32768'));
        $this->assertEquals('32768',        Decimal::fromBinary("\x00\x80\x00"));
    }

    public function testToBinaryThrowsExceptionOnNegativeDecimal()
    {
        $this->setExpectedException('IllegalArgumentException');
        Decimal::toBinary('-273');
    }

    public function testToBinaryThrowsExceptionOnEmptyDecimal()
    {
        $this->setExpectedException('IllegalArgumentException');
        Decimal::toBinary('');
    }

    public function testToBinaryThrowsExceptionOnNullDecimal()
    {
        $this->setExpectedException('IllegalArgumentException');
        Decimal::toBinary(null);
    }

    public function testFromBase64WithInvalidData()
    {
        $this->setExpectedException('IllegalArgumentException');
        Decimal::fromBase64("AB!CD");
    }

    public function testNullBase64ReturnsDefaultValue()
    {
        $this->assertEquals(9999, Decimal::fromBase64(null, 9999));
    }

    public function testEmptyBase64ThrowsException()
    {
        $this->setExpectedException('IllegalArgumentException');
        Decimal::fromBase64('');
    }

    public function testEncodeDecodeOpenIDDHModulus() {
        $modulusAsBinary = pack('H*',
            '00' .  # because this is a positive number, and the first
                    # byte is > 127 (DC == 220).
            'DCF93A0B883972EC0E19989AC5A2CE310E1D37717E8D9571BB762373' .
            '1866E61EF75A2E27898B057F9891C2E27A639C3F29B60814581CD3B2' .
            'CA3986D2683705577D45C2E7E52DC81C7A171876E5CEA74B1448BFDF' .
            'AF18828EFD2519F14E45E3826634AF1949E5B535CC829A483B8A7622' .
            '3E5D490A257F05BDFF16F2FB22C583AB'
        );
        $modulusAsDecimal =
            '15517289818147369747123225776371553991572480196691540447' .
            '97077953140576293785419175806512274236981889937278161526' .
            '46631438561595825688188889951272158842675419950341258706' .
            '55654980358010487053768147672651325574704076585747929129' .
            '15723345106432450947150072296210941943497839259847603755' .
            '94985848253359305585439638443';

        $this->assertEquals(
            $modulusAsBinary,
            Decimal::toBinary($modulusAsDecimal)
        );
        $this->assertEquals(
            $modulusAsDecimal,
            Decimal::fromBinary($modulusAsBinary)
        );
    }
}
?>