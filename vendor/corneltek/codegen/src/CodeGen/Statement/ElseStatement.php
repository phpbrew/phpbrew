<?php
namespace CodeGen\Statement;

use CodeGen\Block;
use CodeGen\Renderable;
use CodeGen\Utils;

class ElseStatement extends Block implements Renderable
{
    public $else;

    public function __construct($block)
    {
        $this->else = Utils::evalCallback($block);
    }

    public function render(array $args = array())
    {
        $this->else->setIndentLevel($this->indentLevel + 1);
        $this[] = ' else {';
        $this[] = $this->else;
        $this[] = '}';
        return parent::render($args);
    }
}
