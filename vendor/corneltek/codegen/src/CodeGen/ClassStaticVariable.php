<?php
namespace CodeGen;

class ClassStaticVariable extends ClassProperty implements Renderable
{
    /**
     * @param array $args
     * @return string
     */
    public function render(array $args = array())
    {
        return Indenter::indent($this->indentLevel) . $this->scope . ' static $' . $this->name . ' = ' . var_export($this->value, true) . ';';
    }

    public function __toString()
    {
        return $this->render();
    }
}

