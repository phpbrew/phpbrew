<?php
namespace CodeGen\Statement;

use CodeGen\Block;
use CodeGen\Renderable;
use CodeGen\Utils;

class ForEachStatement extends Block implements Renderable
{
    /**
     * @var Block
     */
    public $forEachBlock;

    protected $forVariable;
    protected $asVariable;
    protected $keyVariable;

    public function __construct($forVariable, $asVariable = '$item', $keyVariable = NULL, $forEachBlock = NULL)
    {
        parent::__construct();

        $this->forVariable = $forVariable;
        $this->asVariable = $asVariable;
        $this->keyVariable = $keyVariable;

        if ($forEachBlock) {
            $this->forEachBlock = Utils::evalCallback($forEachBlock);
        } else {
            $this->forEachBlock = new Block();
        }
    }

    public function render(array $args = array())
    {
        $this->forEachBlock->setIndentLevel($this->indentLevel + 1);

        if ($this->keyVariable) {
            $this->lines[] = 'foreach (' . $this->forVariable . ' as ' . $this->keyVariable . ' => ' . $this->asVariable . ') {';
        } else {
            $this->lines[] = 'foreach (' . $this->forVariable . ' as ' . $this->asVariable . ') {';
        }

        $this->lines[] = $this->forEachBlock;
        $this->lines[] = '}';

        return parent::render($args);
    }

}







