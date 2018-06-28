<?php
use CodeGen\Raw;
use CodeGen\Variable;
use CodeGen\Expr\MethodCall;
use CodeGen\Testing\CodeGenTestCase;

class MethodCallTest extends CodeGenTestCase
{

    public function testObjectNameVariable()
    {
        $variable = new Variable('$obj2');
        $call = new MethodCall('$obj2');
        $call->method('addStr');
        $call->addArgument('str');
        $this->assertCodeEqualsFile('tests/data/method_call_obj_var_add_str.fixture', $call);
    }

    public function testObjectNameString()
    {
        $call = new MethodCall('$obj');
        $call->method('addStr');
        $call->addArgument('str');
        $this->assertCodeEqualsFile('tests/data/method_call_obj_add_str.fixture', $call);
    }

    public function testString()
    {
        $call = new MethodCall;
        $call->method('addStr');
        $call->addArgument('str');
        $this->assertCodeEqualsFile('tests/data/method_call_add_str.fixture', $call);
    }

    public function testIntArgument()
    {
        $call = new MethodCall;
        $call->method('addInt');
        $call->addArgument(123);
        $this->assertCodeEqualsFile('tests/data/method_call_add_int.fixture', $call);
    }

    public function testComplexArgumentList()
    {
        $call = new MethodCall;
        $call->method('doSomething');
        $call->addArgument(123);
        $call->addArgument('foo');
        $call->addArgument(new Raw('new SplObjectStorage'));
        $call->addArgument(array( 'name' => 'hack' ));
        $this->assertCodeEqualsFile('tests/data/method_call.fixture', $call);
    }
}

