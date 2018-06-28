<?php
namespace CodeGen\Expr;

use CodeGen\ArgumentList;
use CodeGen\Renderable;
use CodeGen\Variable;

/**
 * CallExpr is basically the same thing as method call,
 * but it allows you to change the method call operator.
 */
class FunctionCall implements Renderable
{
    /**
     * @var Variable|string
     */
    public $function;

    public $arguments;

    public function __construct($function, array $arguments = array())
    {
        $this->function = $function;
        $this->arguments = new ArgumentList($arguments);
    }

    public function setArguments(array $args)
    {
        $this->arguments = new ArgumentList($args);
    }

    public function addArgument($arg)
    {
        $this->arguments[] = $arg;
        return $this;
    }

    public function render(array $args = array())
    {
        return $this->function . '(' . $this->arguments->render($args) . ')';
    }

    public function __toString()
    {
        return $this->render();
    }

}



