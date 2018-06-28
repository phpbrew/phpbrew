<?php
namespace CodeGen\Expr;

use CodeGen\ArgumentList;
use CodeGen\Renderable;
use CodeGen\Variable;

class MethodCall implements Renderable
{
    /**
     * @var Variable|string
     */
    public $objectName;

    public $method;

    public $arguments;

    protected $op = '->';

    public function __construct($objectName = '$this', $method = NULL, array $arguments = array())
    {
        $this->objectName = $objectName;
        if ($method) {
            $this->method = $method;
        }
        $this->arguments = new ArgumentList($arguments);
    }

    public function method($name)
    {
        $this->method = $name;
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

        if ($this->objectName instanceof Renderable) {
            $out .= $this->objectName->render($args);
        } else {
            $out .= $this->objectName;
        }
        $out .= $this->op . $this->method . '(' . $this->arguments->render($args) . ')';
        return $out;
    }

    public function __toString()
    {
        return $this->render();
    }

}



