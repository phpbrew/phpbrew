<?php
namespace CodeGen;

use CodeGen\Statement\Statement;

class UseClass extends Statement implements Renderable
{
    public $as;
    public $class;

    public function __construct($class, $as = null)
    {
        $this->class = ltrim($class, '\\');
        $this->as = $as ? ltrim($as, '\\') : null;
    }

    public function getComponents()
    {
        return explode('\\', $this->class);
    }

    public function render(array $args = array())
    {
        $code = 'use ' . $this->class;
        if ($this->as) {
            $code .= ' as ' . $this->as;
        }
        return $code . ';';
    }
}
