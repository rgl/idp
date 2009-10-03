<?php
require_once(dirname(__FILE__).'/../global.php');
require_once('OpenID/QueryString.php');

class OpenID_QueryStringTest extends MyTestCase
{
    public function testDecodeWithDotKeys()
    {
        # Make sure the implementation is not making native PHP
		# translations, eg: the dots in the keys are NOT
        # translated to underscores due to PHP herditage quirks.
        $data = OpenID_QueryString::decode('a.k=1&a.k.y=2&b.c.d=3');
        $this->assertArrayHasKey('a.k', $data);
        $this->assertArrayHasKey('a.k.y', $data);
        $this->assertArrayHasKey('b.c.d', $data);
        $this->assertArrayNotHasKey('a_k', $data);
        $this->assertArrayNotHasKey('a_k_y', $data);
        $this->assertArrayNotHasKey('b_c_d', $data);
    }

    public function testEncodeWithDotKeys()
    {
        $data = array('x.y.z'=>1, 't.r'=>2);
        $q = OpenID_QueryString::encode($data);
        $this->assertEquals('x.y.z=1&t.r=2', $q);
    }

    public function testEncodeWithArrayArguments()
    {
		# we SHOULD not be able to encode Hashes, like we can with native PHP.
		$this->setExpectedException('IllegalArgumentException');
		$data = array(
            'x' => array(1, 2, 3),
            'y' => array('a'=>1, 'b'=>2)
        );
        $q = OpenID_QueryString::encode($data);
    }

    public function testEncodeEmpty()
    {
        $q = OpenID_QueryString::encode('');
        $this->assertEquals('', $q);
    }

    public function testMergeEmpty()
    {
        $q = OpenID_QueryString::merge('http://example/');
        $this->assertEquals('http://example/', $q);
    }

    public function testDecodeWithHashArguments()
    {
		# we should be able to encode what native PHP think are Hashes,
        # but these are retuned has keys.
        # the query string is urlencoded of:
        #   x[0]=1&x[1]=2&x[2]=3&y[a]=1&y[b]=2
        $qs = 'x%5B0%5D=1&x%5B1%5D=2&x%5B2%5D=3&y%5Ba%5D=1&y%5Bb%5D=2';
        $expected = array(
            'x[0]' => '1',
            'x[1]' => '2',
            'x[2]' => '3',
            'y[a]' => '1',
            'y[b]' => '2',
        );
        $actual = OpenID_QueryString::decode($qs);
        $this->assertEquals($expected, $actual);
    }

    public function testDecodeWithArrayArguments()
    {
		# we should not be able to decode what PHP think are Arrays,
        # because this would mean repeated keys (in this case "x[]").
		$this->setExpectedException('IllegalArgumentException');
        # the query string is urlencoded of:
        #   x[]=1&x[]=2&x[]=3
        $qs = 'x%5B%5D=1&x%5B%5D=2&x%5B%5D=3';
        $expected = array('x' => array(1, 2, 3));
        $actual = OpenID_QueryString::decode($qs);
        $this->assertNotEquals($expected, $actual);
    }

    public function testDecodeEmptyQS()
    {
        $actual = OpenID_QueryString::decode('');
        $this->assertEquals(array(), $actual);
    }

    public function testDecodeNullQS()
    {
        $actual = OpenID_QueryString::decode(null);
        $this->assertEquals(array(), $actual);
    }

    public function testDecodeValueWithNewLines()
    {
        $actual = OpenID_QueryString::decode("k=1\r\n2\r\n");
        $this->assertEquals(array('k'=>"1\r\n2\r\n"), $actual);
    }

    public function testDecodeValueWithTabs()
    {
        $actual = OpenID_QueryString::decode("k=1\t2");
        $this->assertEquals(array('k'=>"1\t2"), $actual);
    }

    public function testDecodeValueWithSpaces()
    {
        $actual = OpenID_QueryString::decode("k= 1  2   ");
        $this->assertEquals(array('k'=>" 1  2   "), $actual);
    }

    public function testDecodeWithInvalidNulValue()
    {
        $this->setExpectedException('IllegalArgumentException');
        OpenID_QueryString::decode("badvalue=\x00");
    }

    public function testDecodeWithInvalidNulKey()
    {
        $this->setExpectedException('IllegalArgumentException');
        OpenID_QueryString::decode("bad\x00key=v");
    }

    public function testMerge()
    {
        $expected = '?a=1&b[]=2&a=1a&b=b';
        $actual = OpenID_QueryString::merge('?a=1&b[]=2', array('a'=>'1a', 'b'=>'b'));
        $this->assertEquals($expected, $actual);
    }
}
?>