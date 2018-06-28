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

/**
 * ApcClassLoader implements a wrapping autoloader cached in APC for PHP 5.3.
 *
 * It expects an object implementing a findFile method to find the file. This
 * allows using it as a wrapper around the other loaders of the component (the
 * ClassLoader and the UniversalClassLoader for instance) but also around any
 * other autoloaders following this convention (the Composer one for instance).
 *
 *     // with a Symfony autoloader
 *     use Symfony\Component\ClassLoader\ClassLoader;
 *
 *     $loader = new ClassLoader();
 *     $loader->addPrefix('Symfony\Component', __DIR__.'/component');
 *     $loader->addPrefix('Symfony',           __DIR__.'/framework');
 *
 *     // or with a Composer autoloader
 *     use Composer\Autoload\ClassLoader;
 *
 *     $loader = new ClassLoader();
 *     $loader->add('Symfony\Component', __DIR__.'/component');
 *     $loader->add('Symfony',           __DIR__.'/framework');
 *
 *     $cachedLoader = new ApcClassLoader('my_prefix', $loader);
 *
 *     // activate the cached autoloader
 *     $cachedLoader->register();
 *
 *     // eventually deactivate the non-cached loader if it was registered previously
 *     // to be sure to use the cached one.
 *     $loader->unregister();
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Kris Wallsmith <kris@symfony.com>
 */
class ApcClassLoader
{
    private $prefix;

    /**
     * A class loader object that implements the findFile() method.
     *
     * @var object
     */
    protected $decorated;

    /**
     * Constructor.
     *
     * @param string $prefix    The APC namespace prefix to use.
     * @param object $decorated A class loader object that implements the findFile() method.
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function __construct($prefix, $decorated)
    {
        if (!function_exists('apcu_fetch')) {
            throw new \RuntimeException('Unable to use ApcClassLoader as APC is not installed.');
        }

        if (!method_exists($decorated, 'findFile')) {
            throw new \InvalidArgumentException('The class finder must implement a "findFile" method.');
        }

        $this->prefix = $prefix;
        $this->decorated = $decorated;
    }

    /**
     * Registers this instance as an autoloader.
     *
     * @param bool $prepend Whether to prepend the autoloader or not
     */
    public function register($prepend = false)
    {
        spl_autoload_register(array($this, 'loadClass'), true, $prepend);
    }

    /**
     * Unregisters this instance as an autoloader.
     */
    public function unregister()
    {
        spl_autoload_unregister(array($this, 'loadClass'));
    }

    /**
     * Loads the given class or interface.
     *
     * @param string $class The name of the class
     *
     * @return bool|null True, if loaded
     */
    public function loadClass($class)
    {
        if ($file = $this->findFile($class)) {
            require $file;

            return true;
        }
    }

    /**
     * Finds a file by class name while caching lookups to APC.
     *
     * @param string $class A class name to resolve to file
     *
     * @return string|null
     */
    public function findFile($class)
    {
        $file = apcu_fetch($this->prefix.$class, $success);

        if (!$success) {
            apcu_store($this->prefix.$class, $file = $this->decorated->findFile($class) ?: null);
        }

        return $file;
    }

    /**
     * Passes through all unknown calls onto the decorated object.
     */
    public function __call($method, $args)
    {
        return call_user_func_array(array($this->decorated, $method), $args);
    }
}
