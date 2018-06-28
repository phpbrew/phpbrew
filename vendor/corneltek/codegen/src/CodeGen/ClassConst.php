<?php
namespace CodeGen;

use CodeGen\Statement\Statement;

class ClassConst extends Statement implements Renderable
{
    /**
     * @var Renderable|string
     */
    public $name;

    public $value;

    public function __construct($name, $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    public function render(array $args = array())
    {
        return Indenter::indent($this->indentLevel) . 'const ' . $this->name . ' = ' . var_export($this->value, true) . ';';
    }

    public function __toString()
    {
        return $this->render();
    }

}

