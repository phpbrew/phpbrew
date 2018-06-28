<?php
namespace CodeGen;

/**
 * Method/Function argument
 */
class Argument implements Renderable
{
    protected $name;

    protected $default;

    public function __construct($name, $default = NULL)
    {
        $this->name = $name;
        $this->default = $default;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getDefault()
    {
        return $this->default;
    }

    public function render(array $args = array())
    {
        $code = $this->name;
        if ($this->default) {
            $code .= ' = ' . VariableDeflator::deflate($this->default);
        }
        return $code;
    }

}




