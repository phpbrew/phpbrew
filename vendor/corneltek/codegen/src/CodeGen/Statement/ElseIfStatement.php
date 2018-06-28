<?php
namespace CodeGen\Statement;

use CodeGen\Renderable;

class ElseIfStatement extends IfStatement implements Renderable
{
    public function __construct(Renderable $condition, $elseifblock = NULL)
    {
        parent::__construct($condition, $elseifblock);
    }

    public function render(array $args = array())
    {
        return ' else ' . parent::render($args);
    }
}
