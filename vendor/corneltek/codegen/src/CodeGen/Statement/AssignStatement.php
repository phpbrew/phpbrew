<?php
namespace CodeGen\Statement;

use CodeGen\Expr\AssignExpr;
use CodeGen\Renderable;

class AssignStatement extends Statement implements Renderable
{
    public function __construct($lvalue, $expr)
    {
        $this->expr = new AssignExpr($lvalue, $expr);
    }
}




