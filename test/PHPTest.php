<?php
require_once('global.php');

/**
 * Misc. PHP tests.
 */
class PHPTest extends MyTestCase
{
    public function testStringPaddingWithMultipleLines()
    {
        $this->assertEquals(
            "  multiple\n  line\n  string",
            preg_replace('/^/m', '  ', "multiple\nline\nstring")
        );
    }

    public function testNonExistentArrayKey()
    {
        $array = array();
        $value = @$array['non_existent_key'];
        if ($value !== null)
            $this->fail('non existent key value is not null');
    }

    public function testCompareEmptyString()
    {
        $s = '';
        if (!($s == ''))
            $this->fail('empty strings are not equal?!');
    }

    public function testRegex()
    {
        $regex = '/^[A-Za-z\\d\\/+]+={0,2}$/';
        $this->assertEquals(1, preg_match($regex, 'azAZ'));
        $this->assertEquals(1, preg_match($regex, '09'));
        $this->assertEquals(1, preg_match($regex, 'a/b'));
        $this->assertEquals(1, preg_match($regex, 'a+b'));
        $this->assertEquals(1, preg_match($regex, 'a='));
        $this->assertEquals(0, preg_match($regex, 'a==='));
    }

    public function testDotToUnderscore()
    {
        # keys with dots can be inserted inside $_POST
        $_POST['a.b'] = 1;
        $this->assertArrayHasKey('a.b', $_POST);
        $this->assertArrayNotHasKey('a_b', $_POST);
    }

    public function testDotToUnderscoreParseStr()
    {
        $qs = array();
        parse_str('a.k=1&a.k.y=2&b.c.d=3', $qs);
        $this->assertArrayHasKey('a_k', $qs);
        $this->assertArrayHasKey('a_k_y', $qs);
        $this->assertArrayHasKey('b_c_d', $qs);
        $this->assertArrayNotHasKey('a.k', $qs);
        $this->assertArrayNotHasKey('a.k.y', $qs);
        $this->assertArrayNotHasKey('b.c.d', $qs);
    }
}
?>
