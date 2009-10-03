<?php
require_once('global.php');
require_once('Base64.php');

class Base64Test extends MyTestCase
{
    public function testEncodeOneByte() {
        # base64 expands 3 bytes (or 24-bit) into 4 bytes (or 32 bits).
        # each 6-bit input group is translated into a 8-bit byte.

        #         |-------||-------||-------|
        # 0x01 => 000000 010000 ------ ------
        #           A      Q       =     =
        $this->assertEquals('AQ==', Base64::encode("\x01"));
    }

    public function testDecodeOneByte() {
        $this->assertEquals("\x01", Base64::decode('AQ=='));
    }

    public function testEncodeTwoBytes() {
        #              |-------||-------||-------|
        # 0x01 0x02 => 000000 010000 001000 ------
        #                A      Q      I      =
        $this->assertEquals('AQI=', Base64::encode("\x01\x02"));
    }

    public function testDecodeTwoBytes() {
        $this->assertEquals("\x01\x02", Base64::decode('AQI='));
    }

    public function testEncodeThreeBytes() {
        #                   |-------||-------||-------|
        # 0x01 0x02 0x03 => 000000 010000 001000 000011
        #                     A      Q      I      D
        $this->assertEquals('AQID', Base64::encode("\x01\x02\x03"));
    }

    public function testDecodeThreeBytes() {
        $this->assertEquals("\x01\x02\x03", Base64::decode('AQID'));
    }

    public function testDecodeTwoBytesInvalidPadding() {
        # missing a trailing pad char.
        $this->setExpectedException('IllegalArgumentException');
        Base64::decode('AQI');
    }

    public function testDecodeInvalidChar() {
        $this->setExpectedException('IllegalArgumentException');
        Base64::decode("AQ\nI");
    }

    public function testEncodeNull() {
        $this->setExpectedException('IllegalArgumentException');
        Base64::encode(null);
    }

    public function testEncodeEmpty() {
        $this->setExpectedException('IllegalArgumentException');
        Base64::encode('');
    }

    public function testDecodeInvalidLeadingWhitespace() {
        $this->setExpectedException('IllegalArgumentException');
        Base64::decode(' AQI=');
    }

    public function testDecodeInvalidTrailingWhitespace() {
        $this->setExpectedException('IllegalArgumentException');
        Base64::decode('AQI= ');
    }

    public function testEncodeDecodeOpenIDModulus() {
        $modulusAsBinary = pack('H*',
            'DCF93A0B883972EC0E19989AC5A2CE310E1D37717E8D9571BB762373' .
            '1866E61EF75A2E27898B057F9891C2E27A639C3F29B60814581CD3B2' .
            'CA3986D2683705577D45C2E7E52DC81C7A171876E5CEA74B1448BFDF' .
            'AF18828EFD2519F14E45E3826634AF1949E5B535CC829A483B8A7622' .
            '3E5D490A257F05BDFF16F2FB22C583AB'
        );
        $base64 = Base64::encode($modulusAsBinary);
        $binary = Base64::decode($base64);
        $this->assertEquals($modulusAsBinary, $binary);
    }

    public function testVectorsFromRFC4648() {
        $this->assertEquals('Zg==',     Base64::encode('f'));
        $this->assertEquals('Zm8=',     Base64::encode('fo'));
        $this->assertEquals('Zm9v',     Base64::encode('foo'));
        $this->assertEquals('Zm9vYg==', Base64::encode('foob'));
        $this->assertEquals('Zm9vYmE=', Base64::encode('fooba'));
        $this->assertEquals('Zm9vYmFy', Base64::encode('foobar'));

        $this->assertEquals('f',      Base64::decode('Zg=='));
        $this->assertEquals('fo',     Base64::decode('Zm8='));
        $this->assertEquals('foo',    Base64::decode('Zm9v'));
        $this->assertEquals('foob',   Base64::decode('Zm9vYg=='));
        $this->assertEquals('fooba',  Base64::decode('Zm9vYmE='));
        $this->assertEquals('foobar', Base64::decode('Zm9vYmFy'));
    }
}
?>