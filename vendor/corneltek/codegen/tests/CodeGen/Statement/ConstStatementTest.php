<?php
use CodeGen\Testing\CodeGenTestCase;
use CodeGen\Statement\ConditionalStatement;
use CodeGen\Statement\ConstStatement;
use CodeGen\Statement\Statement;
use CodeGen\Expr\AssignExpr;
use CodeGen\Raw;
use CodeGen\Constant;
use CodeGen\Variable;
use CodeGen\Block;


class ConstStatementTest extends CodeGenTestCase
{
    public function testConstStatement()
    {
        $stmt = new ConstStatement('PHIFTY_ENV', 'development');
        $this->assertCodeEqualsFile('tests/data/const_statement_01.fixture', $stmt);
    }

}
