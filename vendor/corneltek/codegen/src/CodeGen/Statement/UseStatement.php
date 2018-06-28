<?php
namespace CodeGen\Statement;

use CodeGen\Renderable;

class UseStatement extends Statement implements Renderable
{
    protected $as;

    protected $class;

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
            $code .= ' ' . $this->as;
        }
        return $code . ';';
    }
}
