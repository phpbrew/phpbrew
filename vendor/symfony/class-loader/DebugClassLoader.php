<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\ClassLoader;

@trigger_error('The '.__NAMESPACE__.'\DebugClassLoader class is deprecated since version 2.4 and will be removed in 3.0. Use the Symfony\Component\Debug\DebugClassLoader class instead.', E_USER_DEPRECATED);

/**
 * Autoloader checking if the class is really defined in the file found.
 *
 * The DebugClassLoader will wrap all registered autoloaders providing a
 * findFile method and will throw an exception if a file is found but does
 * not declare the class.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Christophe Coevoet <stof@notk.org>
 *
 * @deprecated since version 2.4, to be removed in 3.0.
 *             Use {@link \Symfony\Component\Debug\DebugClassLoader} instead.
 */
class DebugClassLoader
{
    private $classFinder;

    /**
     * Constructor.
     *
     * @param object $classFinder
     */
    public function __construct($classFinder)
    {
        $this->classFinder = $classFinder;
    }

    /**
     * Gets the wrapped class loader.
     *
     * @return object a class loader instance
     */
    public function getClassLoader()
    {
        return $this->classFinder;
    }

    /**
     * Replaces all autoloaders implementing a findFile method by a DebugClassLoader wrapper.
     */
    public static function enable()
    {
        if (!is_array($functions = spl_autoload_functions())) {
            return;
        }

        foreach ($functions as $function) {
            spl_autoload_unregister($function);
        }

        foreach ($functions as $function) {
            if (is_array($function) && !$function[0] instanceof self && method_exists($function[0], 'findFile')) {
                $function = array(new static($function[0]), 'loadClass');
            }

            spl_autoload_register($function);
        }
    }

    /**
     * Unregisters this instance as an autoloader.
     */
    public function unregister()
    {
        spl_autoload_unregister(array($this, 'loadClass'));
    }

    /**
     * Finds a file by class name.
     *
     * @param string $class A class name to resolve to file
     *
     * @return string|null
     */
    public function findFile($class)
    {
        return $this->classFinder->findFile($class) ?: null;
    }

    /**
     * Loads the given class or interface.
     *
     * @param string $class The name of the class
     *
     * @return bool|null True, if loaded
     *
     * @throws \RuntimeException
     */
    public function loadClass($class)
    {
        if ($file = $this->classFinder->findFile($class)) {
            require $file;

            if (!class_exists($class, false) && !interface_exists($class, false) && (!function_exists('trait_exists') || !trait_exists($class, false))) {
                if (false !== strpos($class, '/')) {
                    throw new \RuntimeException(sprintf('Trying to autoload a class with an invalid name "%s". Be careful that the namespace separator is "\" in PHP, not "/".', $class));
                }

                throw new \RuntimeException(sprintf('The autoloader expected class "%s" to be defined in file "%s". The file was found but the class was not in it, the class name or namespace probably has a typo.', $class, $file));
            }

            return true;
        }
    }
}
