<?php
use CodeGen\Statement\TryCatchStatement;
use CodeGen\Testing\CodeGenTestCase;

class TryCatchStatementTest extends CodeGenTestCase
{

    public function testEmptyTryCatch()
    {
        $tryCatch = new TryCatchStatement();
        $this->assertCodeEqualsFile('tests/data/try_catch_empty.fixture', $tryCatch);

    }

    public function testTryCatch()
    {
        $tryCatch = new TryCatchStatement();

        $tryCatch->tryBlock->appendLine('$x =1;');
        $tryCatch->catchBlock->appendLine('throw $e');

        $this->assertCodeEqualsFile('tests/data/try_catch.fixture', $tryCatch);
    }
}
