<?php
use CodeGen\Expr\AssignExpr;
use CodeGen\Raw;
use CodeGen\Statement\Statement;
use CodeGen\Testing\CodeGenTestCase;

class AssignExprTest extends CodeGenTestCase
{
    public function test()
    {
        $assign = new AssignExpr('$foo', 10);
        $statement = new Statement($assign);
        $this->assertCodeEquals('$foo = 10;', $statement);
    }
}

