<?php
namespace CodeGen;

/**
 * A BracketedBlock is a block that uses bracket to wrap the inner block.
 */
class BracketedBlock extends Block
{
    public function render(array $args = array())
    {
        $tab = Indenter::indent($this->indentLevel);
        $this->increaseIndentLevel(); // increaseIndentLevel to indent the inner block.
        $body = '';
        $body .= $tab . "{\n";
        $body .= parent::render($args);
        $body .= $tab . "}\n";
        return $body;
    }

}




