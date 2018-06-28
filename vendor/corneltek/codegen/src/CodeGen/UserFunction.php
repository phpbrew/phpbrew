<?php
namespace CodeGen;

class UserFunction extends Block implements Renderable
{
    public $name;

    public $arguments = array();

    public $bodyArguments = array();

    public $indentLevel = 0;

    /**
     * @var Block
     *
     * The body block
     */
    public $block;

    /**
     * Class Method Constructor
     *
     * @param string $name the function name.
     * @param array $arguments the argument of the function prototype.
     * @param string $body the code of the function.
     * @param array $bodyArguments the template arguments of the code of the function.
     */
    public function __construct($name, array $arguments = array(), $body = '', array $bodyArguments = array())
    {
        $this->name = $name;
        $this->arguments = $arguments;

        $this->block = new BracketedBlock;
        if ($body) {
            $this->block->setBody($body);
        }
        if ($bodyArguments) {
            $this->block->setDefaultArguments($bodyArguments);
        }
    }

    public function getName()
    {
        return $this->name;
    }

    public function setIndentLevel($level)
    {
        $this->indentLevel = $level;
    }

    public function increaseIndentLevel()
    {
        $this->indentLevel++;
    }

    public function decreaseIndentLevel()
    {
        $this->indentLevel--;
    }


    public function setBlock(Block $block)
    {
        $this->block = $block;
    }

    public function getBlock()
    {
        return $this->block;
    }

    public function setArguments(array $args)
    {
        $this->arguments = $args;
    }

    protected function renderArguments()
    {
        return implode(', ', $this->arguments);
    }

    public function render(array $args = array())
    {
        return 'function ' . $this->name . '(' . $this->renderArguments() . ")\n"
        . $this->block->render($args);
    }

}





