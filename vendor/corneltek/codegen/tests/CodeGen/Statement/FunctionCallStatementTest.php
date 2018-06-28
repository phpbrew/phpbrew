<?php
use CodeGen\Expr\AssignExpr;
use CodeGen\Raw;
use CodeGen\Statement\Statement;
use CodeGen\Statement\FunctionCallStatement;
use CodeGen\Testing\CodeGenTestCase;

class FunctionCallStatementTest extends CodeGenTestCase
{
    public function testFunctionCallStatement()
    {
        $stmt = new FunctionCallStatement('spl_autoload_register', array());
        $this->assertCodeEquals('spl_autoload_register();', $stmt);
    }
}

