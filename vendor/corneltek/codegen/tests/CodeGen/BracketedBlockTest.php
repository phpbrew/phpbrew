<?php
use CodeGen\Block;
use CodeGen\BracketedBlock;
use CodeGen\Testing\CodeGenTestCase;

class BracketedBlockTest extends CodeGenTestCase
{
    public function testSimpleBracketedBlock()
    {
        $block = new BracketedBlock;
        $block[] = '$a = 1;';
        $block[] = '$b = 2;';
        $block[] = 'return $a + $b;';
        $this->assertCodeEqualsFile('tests/data/bracketed_block_simple.fixture',$block);
    }

    public function testSimpleBracketedBlockAndSubBlock()
    {
        $block = new BracketedBlock;
        $block[] = '$a = 1;';
        $block[] = '$b = 2;';

        $subblock = new BracketedBlock;
        $subblock[] = '$e = $a + $b;';
        $subblock[] = '$f = $a * $b;';
        $block[] = $subblock;
        $block[] = 'return $a + $b;';
        $this->assertCodeEqualsFile('tests/data/bracketed_block_subblock.fixture', $block);
    }
}

