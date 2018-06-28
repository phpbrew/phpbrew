<?php
use Universal\ClassLoader\Psr4ClassLoader;

class Psr4ClassLoaderTest extends PHPUnit_Framework_TestCase
{
    public function testPsr4()
    {
        $loader = new Psr4ClassLoader;
        $loader->addPrefix('MyBar\\', 'tests/fixtures/class_loader/psr4/simple/');
        $classPath = $loader->resolveClass('MyBar\Foo');
        $this->assertNotNull($classPath);
        $this->assertFileExists($classPath);
    }
}

