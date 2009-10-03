<?php
require_once 'global.php';

class AllTests
{
    public static function tests()
    {
        $tests = array();
        foreach (glob("*Test.php") as $filename)
            $tests[] = $filename;
        foreach (glob("**/*Test.php") as $filename)
            $tests[] = $filename;
        foreach (glob("**/**/*Test.php") as $filename)
            $tests[] = $filename;
        return $tests;
    }

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('IdP');
        foreach (self::tests() as $filename) {
            require_once($filename);
            $testSuite = str_replace('/', '_', $filename);
            $testSuite = str_replace('.php', '', $testSuite);
            $suite->addTestSuite($testSuite);
        }
        return $suite;
    }
}
?>