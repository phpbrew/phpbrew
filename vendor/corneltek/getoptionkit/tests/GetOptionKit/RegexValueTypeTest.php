<?php

use GetOptionKit\ValueType\RegexType;

class RegexValueTypeTest extends PHPUnit_Framework_TestCase
{

    public function testTypeClass() 
    {
        ok( new RegexType );
    }

    public function testOption()
    {
        $regex = new RegexType('#^Test$#');
        $this->assertEquals($regex->option, '#^Test$#');
    }

    public function testValidation()
    {
        $regex = new RegexType('#^Test$#');
        ok( $regex->test('Test') );
        $this->assertFalse($regex->test('test'));

        $regex->option = '/^([a-z]+)$/';
        ok( $regex->test('barfoo') );
        $this->assertFalse($regex->test('foobar234'));
    }
}

