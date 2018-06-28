<?php
use CodeGen\Testing\CodeGenTestCase;
use CodeGen\CommentBlock;

class CommentBlockTest extends CodeGenTestCase
{
    public function testCommentBlock()
    {
        $comment = new CommentBlock;
        $comment[] = 'first line';
        $comment[] = 'second line';
        $this->assertCodeEqualsFile('tests/data/comment_block.fixture',$comment);
    }
}

