<?php
namespace CodeGen\Expr;

use CodeGen\ArgumentList;
use CodeGen\Renderable;

class NewObject implements Renderable
{
    public $className;

    public $arguments;

    public function __construct($className, array $arguments = array())
    {
        $this->className = $className;
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
        return 'new ' . $this->className . '(' . $this->arguments->render($args) . ')';
    }

    public function __toString()
    {
        return $this->render();
    }

}



