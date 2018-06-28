<?php
namespace CodeGen\Expr;

use CodeGen\Renderable;
use CodeGen\Variable;

class ObjectProperty implements Renderable
{
    /**
     * @var Variable|string
     */
    public $objectName;
    public $property;


    protected $op = '->';

    public function __construct($objectName, $property)
    {
        $this->objectName = $objectName;
        $this->property = $property;
    }

    public function render(array $args = array())
    {
        $out = '';

        if ($this->objectName instanceof Renderable) {
            $out .= $this->objectName->render($args);
        } else {
            $out .= $this->objectName;
        }
        $out .= $this->op . $this->property;
        return $out;
    }

    public function __toString()
    {
        return $this->render();
    }

}



