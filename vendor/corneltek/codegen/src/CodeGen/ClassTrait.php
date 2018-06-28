<?php
namespace CodeGen;

use CodeGen\Statement\Statement;

/**
 * use HelloWorld { sayHello as private myPrivateHello; }
 * use HelloWorld { sayHello as protected; }
 *
 * use A, B {
 *      B::smallTalk insteadof A;
 *      A::bigTalk insteadof B;
 * }
 *
 * class Aliased_Talker {
 *    use A, B {
 *      B::smallTalk insteadof A;
 *      A::bigTalk insteadof B;
 *      B::bigTalk as talk;
 *    }
 * }
 */
class ClassTrait extends Statement implements Renderable
{
    public $classes = array();

    public $definitions = array();

    public function __construct(array $classes)
    {
        $this->classes = $classes;
    }

    public function useInsteadOf($aMethod, $b)
    {
        $this->definitions[] = "$aMethod insteadof $b;";
        return $this;
    }

    public function useAs($aMethod, $methodB)
    {
        $this->definitions[] = "$aMethod as $methodB;";
        return $this;
    }

    public function render(array $args = array())
    {
        $out = Indenter::indent($this->indentLevel) . 'use ' . implode(', ', $this->classes);
        if (0 === count($this->definitions)) {
            $out .= ';';
        } else {
            $block = new BracketedBlock;
            foreach ($this->definitions as $def) {
                $block[] = $def;
            }
            $out .= $block->render($args);
        }
        return $out;
    }
}



