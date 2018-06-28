<?php
use CodeGen\Testing\CodeGenTestCase;
use CodeGen\Statement\ConditionalStatement;
use CodeGen\Statement\Statement;
use CodeGen\Expr\AssignExpr;
use CodeGen\Raw;
use CodeGen\Constant;
use CodeGen\Variable;
use CodeGen\Block;

class ConditionalStatementTest extends CodeGenTestCase
{
    public function testConditionalTrue()
    {
        $stmt = new ConditionalStatement(true, '$foo = 1;', '$bar = 1;');
        $this->assertCodeEqualsFile('tests/data/conditional_statement_true.fixture', $stmt);
    }

    public function testConditionalFalse()
    {
        $stmt = new ConditionalStatement(false, '$foo = 1;', '$bar = 1;');
        $this->assertCodeEqualsFile('tests/data/conditional_statement_false.fixture', $stmt);
    }

    public function testConditionalWhen()
    {
        $foo = 2;
        $stmt = new ConditionalStatement($foo == 1, '$foo = 1');
        $stmt->when($foo == 2, function() {
            return '$foo = 2;';
        });
        $stmt->when($foo == 3, function() {
            return '$foo = 3;';
        });
        $this->assertCodeEqualsFile('tests/data/conditional_statement_when.fixture', $stmt);
    }
}

