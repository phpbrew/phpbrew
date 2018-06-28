<?php
namespace CodeGen\Frameworks\PHPUnit;

use CodeGen\Expr\SelfMethodCall;
use CodeGen\Statement\Statement;

function push_if($array, $element)
{
    if ($element) {
        $array[] = $element;
    }
    return $array;
}

class Assertions
{
    static public function assertEquals($expected, $actual, $message = '')
    {
        $expr = new SelfMethodCall('assertEquals', push_if(array($expected, $actual), $message));
        return new Statement($expr);
    }

    static public function assertRegExp($expected, $actual, $message = '')
    {
        $expr = new SelfMethodCall('assertRegExp', push_if(array($expected, $actual), $message));
        return new Statement($expr);
    }

    static public function assertStringMatchesFormat($expected, $actual, $message = '')
    {
        $expr = new SelfMethodCall('assertStringMatchesFormat', push_if(array($expected, $actual), $message));
        return new Statement($expr);
    }

    static public function assertStringMatchesFormatFile($expected, $actual, $message = '')
    {
        $expr = new SelfMethodCall('assertStringMatchesFormatFile', push_if(array($expected, $actual), $message));
        return new Statement($expr);
    }

    static public function assertStringEqualsFile($expected, $actual, $message = '')
    {
        $expr = new SelfMethodCall('assertStringEqualsFile', push_if(array($expected, $actual), $message));
        return new Statement($expr);
    }

    static public function assertStringStartsWith($expected, $actual, $message = '')
    {
        $expr = new SelfMethodCall('assertStringStartsWith', push_if(array($expected, $actual), $message));
        return new Statement($expr);
    }

    static public function assertStringEndsWith($expected, $actual, $message = '')
    {
        $expr = new SelfMethodCall('assertStringEndsWith', push_if(array($expected, $actual), $message));
        return new Statement($expr);
    }


    static public function assertSame($expected, $actual, $message = '')
    {
        $expr = new SelfMethodCall('assertSame', push_if(array($expected, $actual), $message));
        return new Statement($expr);
    }

    static public function assertNull($actual, $message = '')
    {
        $expr = new SelfMethodCall('assertNull', push_if(array($actual), $message));
        return new Statement($expr);
    }

    static public function assertNotEmpty($actual, $message = '')
    {
        $expr = new SelfMethodCall('assertNotEmpty', push_if(array($actual), $message));
        return new Statement($expr);
    }

    static public function assertEmpty($actual, $message = '')
    {
        $expr = new SelfMethodCall('assertEmpty', push_if(array($actual), $message));
        return new Statement($expr);
    }

    static public function assertArrayHasKey($key, array $array, $message = '')
    {
        $expr = new SelfMethodCall('assertArrayHasKey', push_if(array($key, $array), $message));
        return new Statement($expr);
    }

    static public function assertClassHasAttribute($attribute, $class, $message = '')
    {
        $expr = new SelfMethodCall('assertClassHasAttribute', push_if(array($attribute, $class), $message));
        return new Statement($expr);
    }

    static public function assertClassHasStaticAttribute($attribute, $class, $message = '')
    {
        $expr = new SelfMethodCall('assertClassHasStaticAttribute', push_if(array($attribute, $class), $message));
        return new Statement($expr);
    }

    static public function assertContains($element, $array, $message = '')
    {
        $expr = new SelfMethodCall('assertContains', push_if(array($element, $array), $message));
        return new Statement($expr);
    }

    static public function assertContainsOnly($element, $array, $message = '')
    {
        $expr = new SelfMethodCall('assertContainsOnly', push_if(array($element, $array), $message));
        return new Statement($expr);
    }

    static public function assertCount($count, $array, $message = '')
    {
        $expr = new SelfMethodCall('assertCount', push_if(array($count, $array), $message));
        return new Statement($expr);
    }

    static public function assertTrue($val, $message = '')
    {
        $expr = new SelfMethodCall('assertTrue', push_if(array($val), $message));
        return new Statement($expr);
    }

    static public function assertFalse($val, $message = '')
    {
        $expr = new SelfMethodCall('assertFalse', push_if(array($val), $message));
        return new Statement($expr);
    }

    static public function assertFileEquals($fileExpected, $fileActual, $message = '')
    {
        $expr = new SelfMethodCall('assertFileEquals', push_if(array($fileExpected, $fileActual), $message));
        return new Statement($expr);
    }

    static public function assertJsonFileEqualsJsonFile($fileExpected, $fileActual, $message = '')
    {
        $expr = new SelfMethodCall('assertJsonFileEqualsJsonFile', push_if(array($fileExpected, $fileActual), $message));
        return new Statement($expr);
    }

    static public function assertFileExists($fileActual, $message = '')
    {
        $expr = new SelfMethodCall('assertFileExists', push_if(array($fileActual), $message));
        return new Statement($expr);
    }

    static public function assertGreaterThan($expected, $actual, $message = '')
    {
        $expr = new SelfMethodCall('assertGreaterThan', push_if(array($expected, $actual), $message));
        return new Statement($expr);
    }

    static public function assertGreaterThanOrEqual($expected, $actual, $message = '')
    {
        $expr = new SelfMethodCall('assertGreaterThanOrEqual', push_if(array($expected, $actual), $message));
        return new Statement($expr);
    }

    static public function assertLessThan($expected, $actual, $message = '')
    {
        $expr = new SelfMethodCall('assertLessThan', push_if(array($expected, $actual), $message));
        return new Statement($expr);
    }

    static public function assertLessThanOrEqual($expected, $actual, $message = '')
    {
        $expr = new SelfMethodCall('assertLessThanOrEqual', push_if(array($expected, $actual), $message));
        return new Statement($expr);
    }


    static public function assertInstanceOf($expected, $actual, $message = '')
    {
        $expr = new SelfMethodCall('assertInstanceOf', push_if(array($expected, $actual), $message));
        return new Statement($expr);
    }

    static public function assertInternalType($expected, $actual, $message = '')
    {
        $expr = new SelfMethodCall('assertInternalType', push_if(array($expected, $actual), $message));
        return new Statement($expr);
    }

}



