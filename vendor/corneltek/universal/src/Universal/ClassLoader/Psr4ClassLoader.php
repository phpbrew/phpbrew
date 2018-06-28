<?php
/**
 * This file is part of the Universal package.
 *
 * (c) Yo-An Lin <yoanlin93@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Universal\ClassLoader;
use Universal\ClassLoader\ClassLoader;

class Psr4ClassLoader implements ClassLoader
{
    protected $prefixes = array();

    public function __construct(array $prefixes = array()) 
    {
        $this->prefixes = $prefixes;
    }

    public function addPrefix($prefix, $baseDir, $trim = false)
    {
        if ($trim) {
            $prefix = trim($prefix, '\\') . '\\';
            $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        }
        $this->prefixes[] = array($prefix, $baseDir);
    }


    public function addPrefixes(array $prefixes, $trim = false)
    {
        foreach ($prefixes as $prefix => $baseDir) {
            $this->addPrefix($prefix, $baseDir, $trim);
        }
    }


    /**
     * find class file path
     *
     * @param string $fullclass
     */
    public function resolveClass($fullclass)
    {
        # echo "Fullclass: " . $fullclass . "\n";
        foreach ($this->prefixes as $prefixMap) {
            list($prefix, $dir) = $prefixMap;
            if (strpos($fullclass, $prefix) === 0) {
                $len = strlen($prefix);
                $classSuffix = substr($fullclass, $len);
                $subpath = str_replace('\\', DIRECTORY_SEPARATOR, $classSuffix) . '.php';
                $classPath = $dir . $subpath;
                if (file_exists($classPath)) {
                    return $classPath;
                }
            }
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

