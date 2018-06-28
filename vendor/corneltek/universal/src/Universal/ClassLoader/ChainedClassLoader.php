<?php
namespace Universal\ClassLoader;

class ChainedClassLoader implements ClassLoader
{
    protected $classloaders = array();

    public function __construct(array $classloaders = array())
    {
        $this->classloaders = $classloaders;
    }

    public function resolveClass($fullClass)
    {
        foreach ($this->classloaders as $loader) {
            if ($classPath = $loader->resolveClass($fullClass)) {
                return $classPath;
            }
        }
    }

    public function loadClass($class)
    {
        if ($file = $this->resolveClass($class)) {
            require $file;
        }
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

    /**
     * unregister the spl autoloader
     */
    public function unregister()
    {
        spl_autoload_unregister(array($this, 'loadClass'));
    }

}



