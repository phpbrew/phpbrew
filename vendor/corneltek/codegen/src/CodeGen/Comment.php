<?php
namespace CodeGen;

use CodeGen\Exception\InvalidArgumentTypeException;

class Comment extends Line implements Renderable
{
    public $comment;

    public function __construct($comment)
    {
        $this->comment = $comment;
    }

    public function render(array $args = array())
    {
        $tab = Indenter::indent($this->indentLevel);
        $out = $tab . '// ';
        if (is_string($this->comment)) {
            $out .= $this->comment;
        } else if ($this->comment instanceof Renderable) {
            $out .= $this->comment->render($args);
        } else {
            throw new InvalidArgumentTypeException('Invalid type for comment.', $this->comment, ['string', 'Renderable']);
        }
        return $out;
    }

}




