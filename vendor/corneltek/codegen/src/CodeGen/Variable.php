<?php
namespace CodeGen;

class Variable implements Renderable
{
    protected $name;

    protected $templateApply = false;

    protected $templateArgs = array();

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function templateApply(array $templateArgs = array())
    {
        $this->templateApply = true;
        $this->templateArgs = $templateArgs;
    }

    static public function template($name, array $args = array())
    {
        $var = new self($name);
        $var->templateApply($args);
        return $var;
    }

    public function render(array $args = array())
    {
        if ($this->templateApply) {
            return Utils::renderStringTemplate($this->name, array_merge($this->templateArgs, $args));
        }
        return $this->name;
    }
}





