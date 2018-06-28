<?php
namespace CodeGen;


/**
 * @codeCoverageIgnore
 */
abstract class Line implements Renderable
{

    public $indentLevel = 0;


    public $content;

    public function __construct($content = NULL)
    {
        $this->content = $content;
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

    public function render(array $args = array())
    {
        // XXX: apply template here
        return $this->content;
    }

    public function __toString()
    {
        return $this->render();
    }
}



