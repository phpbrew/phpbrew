<?php
namespace CodeGen;

use CodeGen\Statement\Statement;

class ClassProperty extends Statement implements Renderable
{
    public $name;
    public $scope = 'public';
    public $value;

    public function __construct($name, $value, $scope = 'public')
    {
        $this->name = $name;
        $this->value = $value;
        $this->scope = $scope;
    }


    public function render(array $args = array())
    {
        $code = Indenter::indent($this->indentLevel) . $this->scope . ' $' . $this->name;
        if ($this->value) {
            $code .= ' = ' . var_export($this->value, true);
        }
        return $code . ';';
    }

    public function __toString()
    {
        return $this->render();
    }


}

