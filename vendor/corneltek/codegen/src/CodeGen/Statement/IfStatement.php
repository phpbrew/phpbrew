<?php
namespace CodeGen\Statement;

use CodeGen\Block;
use CodeGen\Renderable;
use CodeGen\Utils;
use CodeGen\VariableDeflator;

class IfStatement extends Block implements Renderable
{
    protected $condition;

    public $if;

    protected $else;

    protected $elseifs = array();

    public function __construct(Renderable $condition, $block = NULL)
    {
        $this->condition = $condition;
        if ($block) {
            $this->if = Utils::evalCallback($block);
        } else {
            $this->if = new Block;
        }
    }

    /**
     * This method was named 'elif' here because we can't use 'elseif' or 'elseIf' as the
     * method name.
     *
     * @param Expr $condition
     * @param Block $block
     */
    public function elif($condition, $block)
    {
        $this->elseifs[] = new ElseIfStatement($condition, $block);
        return $this;
    }

    public function __call($method, $args)
    {
        if ($method === 'else') {
            return $this->_else($args[0]);
        }
    }

    public function _else($block)
    {
        $this->else = new ElseStatement($block);
        return $this;
    }

    public function render(array $args = array())
    {
        $this->if->setIndentLevel($this->indentLevel + 1);
        $this[] = 'if (' . VariableDeflator::deflate($this->condition) . ') {';
        $this[] = $this->if;

        $trailingBlocks = array();
        if (!empty($this->elseifs)) {
            foreach ($this->elseifs as $elseIf) {
                $trailingBlocks[] = rtrim($elseIf->render($args));
            }
        }
        if ($this->else) {
            $trailingBlocks[] = rtrim($this->else->render($args));
        }

        $this[] = '}' . join('', $trailingBlocks);
        return parent::render($args);
    }

}







