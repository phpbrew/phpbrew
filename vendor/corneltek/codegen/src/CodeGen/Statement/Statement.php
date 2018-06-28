<?php
namespace CodeGen\Statement;

use CodeGen\Line;
use CodeGen\Renderable;

class Statement extends Line implements Renderable
{
    /**
     * @var Renderable
     */
    public $expr;

    public function __construct(Renderable $expr)
    {
        $this->expr = $expr;
    }

    /**
     * @param array $args
     * @return string
     */
    public function render(array $args = array())
    {
        return $this->expr->render($args) . ';';
    }

}



