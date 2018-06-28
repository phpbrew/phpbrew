<?php
namespace CodeGen;

use ArrayAccess;
use ArrayIterator;
use CodeGen\Exception\InvalidArgumentTypeException;
use IteratorAggregate;


/**
 * A block class can generate multiple-linke block code.
 *
 * It uses line-based unit to generate code, however the added
 * element doesn't have to be string, it can be anything
 * stringify-able objects (support __toString() method) or
 * implemented with Renderable interface.
 */
class Block implements IteratorAggregate, ArrayAccess, Renderable
{
    public $lines = array();

    public $args = array();

    public function __construct(array $lines = array())
    {
        $this->lines = $lines;
    }

    /**
     * The default indent level.
     */
    public $indentLevel = 0;

    public function setDefaultArguments(array $args)
    {
        $this->args = $args;
        return $this;
    }

    public function setLines(array $lines)
    {
        $this->lines = $lines;
        return $this;
    }

    /**
     * Allow text can be set with array
     */
    public function setBody($text)
    {
        if (is_string($text)) {
            $this->lines = explode("\n", $text);
        } elseif (is_array($text)) {
            $this->lines = $text;
        } else {
            throw new InvalidArgumentTypeException('Invalid body type', $text, array('string', 'array'));
        }
    }

    public function appendRenderable(Renderable $obj)
    {
        $this->lines[] = $obj;
    }

    public function appendLine($line)
    {
        $this->lines[] = $line;
    }

    public function increaseIndentLevel()
    {
        $this->indentLevel++;
        return $this;
    }

    public function decreaseIndentLevel()
    {
        $this->indentLevel--;
        return $this;
    }

    public function indent()
    {
        $this->indentLevel++;
    }

    public function unindent()
    {
        $this->indentLevel--;
    }

    public function splice($from, $length, array $replacement = array())
    {
        return array_splice($this->lines, $from, $length, $replacement);
    }


    public function setIndentLevel($indent)
    {
        $this->indentLevel = $indent;
    }

    public function render(array $args = array())
    {
        $tab = Indenter::indent($this->indentLevel);
        $body = '';
        foreach ($this->lines as $line) {
            if (is_string($line)) {
                $body .= $tab . $line . "\n";
            } else if ($line instanceof Renderable) {
                $subbody = rtrim($line->render()); // trim the trailing white-space
                $sublines = explode("\n", $subbody);
                foreach ($sublines as $subline) {
                    $body .= $tab . $subline . "\n";
                }
            } else {
                //var_dump( $line );
                throw new InvalidArgumentTypeException('Unsupported line object type', $line, array('string', 'Renderable'));
            }
        }
        return Utils::renderStringTemplate($body, array_merge($this->args, $args));
    }

    // ============ interface ArrayAggregator implementation =============
    public function getIterator()
    {
        return new ArrayIterator($this->lines);
    }

    // ============ interface ArrayAccess implementation =============
    public function offsetSet($key, $value)
    {
        if ($key) {
            $this->lines[$key] = $value;
        } else {
            $this->lines[] = $value;
        }
    }

    public function offsetExists($key)
    {
        return isset($this->lines[$key]);
    }

    public function offsetGet($key)
    {
        return $this->lines[$key];
    }

    public function offsetUnset($key)
    {
        unset($this->lines[$key]);
    }


}




