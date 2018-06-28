<?php
use CodeGen\Statement\ForEachStatement;
use CodeGen\Testing\CodeGenTestCase;

class ForEachStatementTest extends CodeGenTestCase
{
    public function testForEachStatement()
    {
        $cls = new ForEachStatement('$iterator', '$item');
        $cls->forEachBlock->appendLine('$item;');

        $this->assertCodeEqualsFile('tests/data/fore_each_statement.fixture', $cls);
    }

    public function testForEachStatementKey()
    {
        $cls = new ForEachStatement('$iterator', '$item', '$key');
        $cls->forEachBlock->appendLine('$item;');
        $cls->forEachBlock->appendLine('$key;');

        $this->assertCodeEqualsFile('tests/data/fore_each_statement_key.fixture', $cls);
    }
}
