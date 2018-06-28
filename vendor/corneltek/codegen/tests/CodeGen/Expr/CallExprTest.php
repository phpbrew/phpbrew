<?php
use CodeGen\Expr\CallExpr;
use CodeGen\Testing\CodeGenTestCase;
use CodeGen\Statement\Statement;

class CallExprTest extends CodeGenTestCase
{
    public function testSimpleMethodCall()
    {
        $call = new CallExpr('$foo','->','bar');
        $statement = new Statement($call);
        $this->assertCodeEquals('$foo->bar();', $statement);
    }

    public function testStaticMethodCall()
    {
        $call = new CallExpr('Some','::','bar');
        $statement = new Statement($call);
        $this->assertCodeEquals('Some::bar();', $statement);
    }

    public function testFunctionCall()
    {
        $call = new CallExpr(null,null,'spl_autoload_register');
        $statement = new Statement($call);
        $this->assertCodeEquals('spl_autoload_register();', $statement);
    }
}

