<?php
namespace CodeGen;

use Exception;

class CommentBlock extends Block implements Renderable
{
    public function render(array $args = array())
    {
        $tab = Indenter::indent($this->indentLevel);
        $body = '';

        $body .= $tab . "/**\n";

        foreach ($this->lines as $line) {
            if (is_string($line)) {
                $body .= $tab . ' * ' . $line . "\n";
            } else if ($line instanceof Renderable) {
                $subbody = rtrim($line->render()); // trim the trailing white-space
                $sublines = explode("\n", $subbody);
                foreach ($sublines as $subline) {
                    $body .= $tab . ' * ' . $subline . "\n";
                }
            } else {
                throw new Exception('Unsupported line object.');
            }
        }
        $body .= " */\n";
        return Utils::renderStringTemplate($body, array_merge($this->args, $args));
    }

}




