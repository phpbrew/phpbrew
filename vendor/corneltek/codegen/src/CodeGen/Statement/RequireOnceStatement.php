<?php
namespace CodeGen\Statement;

use CodeGen\Renderable;

class RequireOnceStatement extends Statement implements Renderable
{
    public function render(array $args = array())
    {
        if ($this->expr instanceof Renderable) {
            return 'require_once ' . $this->expr->render($args) . ';';
        } else {
            return 'require_once ' . var_export($this->expr, true) . ';';
        }
    }
}



