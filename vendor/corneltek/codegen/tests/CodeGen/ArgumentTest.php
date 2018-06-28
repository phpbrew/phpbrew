<?php
use CodeGen\Testing\CodeGenTestCase;
use CodeGen\Statement\RequireStatement;
use CodeGen\Statement\RequireOnceStatement;
use CodeGen\Statement\Statement;
use CodeGen\Expr\AssignExpr;
use CodeGen\Raw;
use CodeGen\Constant;
use CodeGen\Argument;
use CodeGen\Variable;
use CodeGen\Block;

class ArgumentTest extends CodeGenTestCase
{
    public function testArgumentWithoutDefaultValue()
    {
        $a = new Argument('$foo');
        $this->assertCodeEqualsFile('tests/data/argument.fixture', $a);
    }

    public function testArgumentWithDefaultValue()
    {
        $a = new Argument('$foo', 333);
        $this->assertEquals('$foo', $a->getName());
        $this->assertEquals(333, $a->getDefault());
        $this->assertCodeEqualsFile('tests/data/argument_default.fixture', $a);
    }
}

