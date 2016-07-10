<?php
namespace PhpBrew;

use Exception;
use RuntimeException;
use PhpBrew\Exception\OopsException;
use PhpBrew\Build;

use ArrayAccess;
use IteratorAggregate;
use ArrayIterator;

class VariantCollection implements ArrayAccess, IteratorAggregate
{
    protected $variants = array();

    public function variant($name, $desc = null)
    {
        $variant = $this->variants[ $name ] = new Variant($name);
        if ($desc) {
            $variant->desc($desc);
        }
        return $variant;
    }

    public function offsetGet($key)
    {
        if (isset($this->variants[$key])) {
            return $this->variants[$key];
        }
    }
    public function offsetSet($key, $variant)
    {
        return $this->variants[$key] = $variant;
    }

    public function offsetExists($key)
    {
        return isset($this->variants[$key]);
    }

    public function offsetUnset($key)
    {
        unset($this->variants[$key]);
    }

    public function getIterator()
    {
        return new ArrayIterator($this->variants);
    }
}
