<?php
use CodeGen\UserFunction;

class UserFunctionTest extends PHPUnit_Framework_TestCase
{
    public function testSimpleUserFunction()
    {
        $func = new UserFunction('foo', array('$a' , '$b' ));
        $block = $func->getBlock();
        $block[] = 'return $a + $b;';
        $this->assertStringEqualsFile('tests/data/simple_user_func.fixture', $func->render());
    }
}

