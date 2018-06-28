<?php
namespace CodeGen\Statement;

use CodeGen\Renderable;

class RequireStatement extends Statement implements Renderable
{
    public function __construct($file)
    {
        $this->expr = $file;
    }

    public function render(array $args = array())
    {
        if ($this->expr instanceof Renderable) {
            return 'require ' . $this->expr->render($args) . ';';
        } else {
            return 'require ' . var_export($this->expr, true) . ';';
        }
    }

}



