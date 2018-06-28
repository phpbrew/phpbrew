<?php
namespace CodeGen;

class ClassName implements Renderable
{
    public $name;

    public $namespace;

    public $root = false;

    public function __construct($className)
    {
        if ($className[0] === '\\') {
            $this->root = true;
            $className = substr($className, 1);
        }

        // parse namespace
        if (strpos($className, '\\') !== false) {
            $p = explode('\\', ltrim($className, '\\'));
            $this->name = end($p);
            $this->namespace = implode('\\', array_splice($p, 0, count($p) - 1));
        } else {
            $this->name = $className;
        }
    }


    /**
     * @return string return short class name
     */
    public function getName()
    {
        return $this->name;
    }


    public function setNamespace($ns)
    {
        $this->namespace = $ns;
        return $this;
    }

    /**
     * This method followe ReflectionClass's interface.
     *
     * @return boolean return true if the class name is in namespace.
     */
    public function inNamespace()
    {
        return $this->namespace ? true : false;
    }

    /**
     * This method followe ReflectionClass's interface
     *
     * @return string return namespace name
     */
    public function getNamespaceName()
    {
        return $this->namespace;
    }


    public function getFullName()
    {
        if ($this->namespace) {
            return ($this->root ? '\\' : '') . $this->namespace . '\\' . $this->name;
        } else {
            return ($this->root ? '\\' : '') . $this->name;
        }
    }

    public function render(array $args = array())
    {
        return $this->getFullName();
    }

    public function __toString()
    {
        return $this->render();
    }
}

