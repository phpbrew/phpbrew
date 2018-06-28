<?php
use Universal\ClassLoader\Psr0ClassLoader;

class Psr0ClassLoaderTest extends PHPUnit_Framework_TestCase
{
    public function testPsr0ClassLoader()
    {
        $loader = new Psr0ClassLoader;
        $loader->addNamespace('Universal\\ClassLoader', 'src');
        $loader->addNamespace('Universal', 'src');
        $classPath = $loader->resolveClass('Universal\\ClassLoader\\Psr0ClassLoader');
        $this->assertNotNull($classPath);
        $this->assertFileExists($classPath);
    }
}

