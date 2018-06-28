<?php
use CodeGen\UserFunction;

class UserFunctionEvaluationTest extends PHPUnit_Framework_TestCase
{
    public function testUserFunc()
    {
        $func = new UserFunction('user_foo', array('$i', '$x = 2'), 'return $i + $x;');
        ok($func);
        ok($func->render());
        eval($func->render());

        // echo $func->__toString();

        is(3, user_foo(1));
        is(2, user_foo(1,1));
        is(3, user_foo(1,2));

    }

    public function testUserFuncWithBody()
    {
        $func = new UserFunction('user_foo_body', array('$i', '$x = 2'), 'return $i + $x * {{f}};', array('f' => 100 ));
        ok($func);
        ok($func->render());
        eval($func->render());

        // echo $func->__toString();

        is(201, user_foo_body(1));
        is(101, user_foo_body(1,1));
        is(201, user_foo_body(1,2));
    }
}

