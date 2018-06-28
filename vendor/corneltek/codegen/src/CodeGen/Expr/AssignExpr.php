<?php
namespace CodeGen\Expr;

use CodeGen\Renderable;
use CodeGen\VariableDeflator;

class AssignExpr implements Renderable
{

    protected $lvalue;

    protected $expr;

    public function __construct($lvalue, $expr)
    {
        $this->lvalue = $lvalue;
        $this->expr = $expr;
    }


    public function render(array $args = array())
    {
        return VariableDeflator::deflate($this->lvalue) . ' = ' . VariableDeflator::deflate($this->expr);
    }

}




