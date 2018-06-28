<?php
namespace Universal\ClassLoader;


class MapClassLoader
{

    protected $classMap;

    public function __construct(array $map)
    {
        $this->classMap = $map;
    }


    /**
     * find class file path
     *
     * @param string $fullclass
     */
    public function resolveClass($fullclass)
    {
        if (isset($this->classMap[$fullclass])) {
            return $this->classMap[$fullclass];
        }
    }

    public function loadClass($class)
    {
        if ($file = $this->resolveClass($class)) {
            require $file;
            return true;
        }
        return false;
    }


    /**
     * register to spl_autoload_register
     *
     * @param boolean $prepend
     */
    public function register($prepend = false)
    {
        spl_autoload_register(array($this, 'loadClass'), true, $prepend);
    }

    public function unregister()
    {
        spl_autoload_unregister(array($this, 'loadClass'));
    }
    
}


