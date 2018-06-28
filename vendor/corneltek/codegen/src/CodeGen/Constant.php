<?php
namespace CodeGen;

class Constant implements Renderable
{
    protected $val;

    public function __construct($val)
    {
        $this->val = $val;
    }

    public function render(array $args = array())
    {
        if (is_scalar($this->val)) {
            return var_export($this->val, true);
        }
        return $this->val;
    }
}





