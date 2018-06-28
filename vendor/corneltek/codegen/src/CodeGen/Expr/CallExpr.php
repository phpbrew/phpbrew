<?php
namespace CodeGen\Expr;

use CodeGen\ArgumentList;
use CodeGen\Renderable;
use CodeGen\Variable;

/**
 * CallExpr is basically the same thing as method call,
 * but it allows you to change the method call operator.
 */
class CallExpr implements Renderable
{
    /**
     * @var Variable|string
     */
    public $objectName;

    public $method;

    public $arguments;

    protected $op;

    public function __construct($objectName = null, $op = '->', $method, array $arguments = array())
    {
        $this->objectName = $objectName;
        $this->op = $op;
        $this->method = $method;
        $this->arguments = new ArgumentList($arguments);
    }

    public function method($method)
    {
        $this->method = $method;
        return $this;
    }

    public function op($op)
    {
        $this->op = $op;
        return $this;
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
        $out = '';
        if ($this->objectName) {
            if ($this->objectName instanceof Renderable) {
                $out .= $this->objectName->render($args);
            } else {
                $out .= $this->objectName;
            }
            $out .= $this->op;
        }
        $out .= $this->method . '(' . $this->arguments->render($args) . ')';
        return $out;
    }

    public function __toString()
    {
        return $this->render();
    }

}



