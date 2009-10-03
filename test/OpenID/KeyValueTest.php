<?php
require_once(dirname(__FILE__).'/../global.php');
require_once('OpenID/KeyValue.php');

class OpenID_KeyValueTest extends MyTestCase
{
    public function testEncode() {
        $this->assertEquals(
            "key:value\n",
            OpenID_KeyValue::encode(array('key' => 'value'))
        );
        $this->assertEquals(
            "key1:value1\nkey2:value2\n",
            OpenID_KeyValue::encode(
                array(
                    'key1' => 'value1',
                    'key2' => 'value2'
                )
            )
        );
    }

    public function testEncodeWithKeysAndValues() {
        $this->assertEquals(
            "key:value\n",
            OpenID_KeyValue::encode(array('key' => 'value'), array('key'))
        );
        # test ordered encoding.
        $this->assertEquals(
            "z:zvalue\na:avalue\nZ:Zvalue\n",
            OpenID_KeyValue::encode(
                array(
                    'a' => 'avalue',
                    'Z' => 'Zvalue',
                    'z' => 'zvalue',
                ),
                array('z', 'a', 'Z')
            )
        );
    }

    public function testEncodeCannotHaveColonOnKey()
    {
        $this->setExpectedException('IllegalArgumentException');
        OpenID_KeyValue::encode(array('ke:y' => ''));
    }

    public function testEncodeCannotHaveNewLineOnKey()
    {
        $this->setExpectedException('IllegalArgumentException');
        OpenID_KeyValue::encode(array("ke\ny" => ''));
    }

    public function testEncodeCannotHaveNewLineOnValue()
    {
        $this->setExpectedException('IllegalArgumentException');
        OpenID_KeyValue::encode(array("key" => "val\nue"));
    }

    public function testEncodeCannotHaveWhitespaceAtStartOffValue()
    {
        $this->setExpectedException('IllegalArgumentException');
        OpenID_KeyValue::encode(array("key" => '  value'));
    }

    public function testEncodeCannotHaveWhitespaceAtEndOffValue()
    {
        $this->setExpectedException('IllegalArgumentException');
        OpenID_KeyValue::encode(array("key" => 'value  '));
    }

    public function testDecode()
    {
        $this->assertEquals(
            array('key' => 'value'),
            OpenID_KeyValue::decode("key:value\n")
        );
        $this->assertEquals(
            array(
                'key1' => 'value1',
                'key2' => 'value2'
            ),
            OpenID_KeyValue::decode("key1:value1\nkey2:value2\n")
        );
    }

    public function testDecodeCannotHaveRepeatedKeys()
    {
        $this->setExpectedException('IllegalArgumentException');
        OpenID_KeyValue::decode("key:value\nkey:value\n");
    }
}
?>