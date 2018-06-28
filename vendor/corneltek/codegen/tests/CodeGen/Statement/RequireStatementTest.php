<?php
use CodeGen\Testing\CodeGenTestCase;
use CodeGen\Statement\RequireStatement;
use CodeGen\Statement\RequireOnceStatement;
use CodeGen\Statement\Statement;
use CodeGen\Expr\AssignExpr;
use CodeGen\Raw;
use CodeGen\Constant;
use CodeGen\Variable;
use CodeGen\Block;

class RequireStatementTest extends CodeGenTestCase
{
    public function testRequire()
    {
        $varfile = new Constant('file.php');
        $requireStmt = new RequireStatement($varfile);
        $this->assertCodeEqualsFile('tests/data/require_statement.fixture', $requireStmt);
    }


    public function testRequireByVariable()
    {
        $varfile = new Variable('$file');
        $requireStmt = new RequireStatement($varfile);
        $this->assertCodeEqualsFile('tests/data/require_statement_by_var.fixture', $requireStmt);
    }

    public function testRequireOnce()
    {
        $varfile = new Constant('file.php');
        $requireStmt = new RequireOnceStatement($varfile);
        $this->assertCodeEqualsFile('tests/data/require_once_statement.fixture', $requireStmt);
    }
}

