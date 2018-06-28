<?php
namespace CodeGen\Statement;

use CodeGen\Block;
use CodeGen\Renderable;
use CodeGen\Utils;
use CodeGen\VariableDeflator;

class IfElseStatement extends IfStatement implements Renderable
{
    public $else;

    protected $elifs = array();

    public function __construct(Renderable $condition, $ifBlock = NULL, $elseBlock = NULL)
    {
        parent::__construct($condition, $ifBlock);

        if ($elseBlock) {
            $this->else = Utils::evalCallback($elseBlock);
        } else {
            $this->else = new Block;
        }
    }

    public function render(array $args = array())
    {
        $this->if->setIndentLevel($this->indentLevel + 1);
        $this->else->setIndentLevel($this->indentLevel + 1);

        $this[] = 'if (' . VariableDeflator::deflate($this->condition) . ') {';
        $this[] = $this->if;
        $this[] = '} else {';
        $this[] = $this->else;
        $this[] = '}';

        return Block::render($args);
    }
}
