<?php
use CodeGen\Block;
use CodeGen\Constant;
use CodeGen\Expr\AssignExpr;
use CodeGen\Statement\IfElseStatement;
use CodeGen\Statement\Statement;
use CodeGen\Testing\CodeGenTestCase;
use CodeGen\Variable;

class IfElseStatementTest extends CodeGenTestCase
{
    public function testIfElseStatement()
    {
        $foo = new Variable('$foo');
        $ifFoo = new IfElseStatement($foo, function () use ($foo) {
            $block = new Block;
            $block[] = new Statement(new AssignExpr($foo, new Constant(30)));
            return $block;
        }, function () use ($foo) {
            $block = new Block;
            $block[] = new Statement(new AssignExpr($foo, new Constant(20)));
            return $block;
        });
        $this->assertCodeEqualsFile('tests/data/if_else_statement.fixture', $ifFoo);
    }

    public function testIfElseStatementNoArg()
    {
        $foo = new Variable('$foo');
        $ifFoo = new IfElseStatement($foo);
        $this->assertCodeEqualsFile('tests/data/if_else_statement_no_args.fixture', $ifFoo);
    }

    public function testIfElseStatementNoArgBlocks()
    {
        $foo = new Variable('$foo');
        $ifFoo = new IfElseStatement($foo);

        $ifBlock = $ifFoo->if;
        $ifBlock->appendRenderable(
            new Statement(new AssignExpr($foo, new Constant(20)))
        );
        $elseBlock = $ifFoo->else;
        $elseBlock->appendRenderable(
            new Statement(new AssignExpr($foo, new Constant(20)))
        );


        $this->assertCodeEqualsFile('tests/data/if_else_statement_no_args_block.fixture', $ifFoo);
    }
}

