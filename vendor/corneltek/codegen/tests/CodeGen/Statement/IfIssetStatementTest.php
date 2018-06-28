<?php
use CodeGen\Testing\CodeGenTestCase;
use CodeGen\Statement\IfStatement;
use CodeGen\Statement\IfIssetStatement;
use CodeGen\Statement\IfElseStatement;
use CodeGen\Statement\Statement;
use CodeGen\Expr\AssignExpr;
use CodeGen\Raw;
use CodeGen\Variable;
use CodeGen\Constant;
use CodeGen\Block;

class IfIssetStatementTest extends CodeGenTestCase
{
    public function testIfIssetStatement()
    {
        $foo = new Variable('$foo');
        $ifFoo = new IfIssetStatement($foo, 'key', function() use ($foo) {
            $block = new Block;
            $block[] = new Statement(new AssignExpr($foo, new Constant(30)));
            return $block;
        });
        $this->assertCodeEqualsFile('tests/data/if_isset_statement.fixture', $ifFoo);
    }

    public function testIfIssetMultipleKeysStatement()
    {
        $foo = new Variable('$foo');
        $ifFoo = new IfIssetStatement($foo, array('key', 'key2', 0), function() use ($foo) {
            $block = new Block;
            $block[] = new Statement(new AssignExpr($foo, new Constant(30)));
            return $block;
        });
        $this->assertCodeEqualsFile('tests/data/if_isset_statement_multiple_keys.fixture', $ifFoo);
    }
}

