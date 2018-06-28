<?php
use CodeGen\Expr\AssignExpr;
use CodeGen\Raw;
use CodeGen\Statement\Statement;
use CodeGen\Statement\UseStatement;
use CodeGen\Testing\CodeGenTestCase;

class UseStatementTest extends CodeGenTestCase
{
    public function testUseStatement()
    {
        $stmt = new UseStatement('Foo\Bar');
        $this->assertCodeEquals('use Foo\Bar;', $stmt);
    }
}



