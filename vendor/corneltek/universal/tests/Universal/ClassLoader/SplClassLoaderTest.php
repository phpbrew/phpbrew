<?php

class SplClassLoaderTest extends PHPUnit_Framework_TestCase
{
    public function testAddPrefix()
    {
        $loader = new \Universal\ClassLoader\SplClassLoader( array(  
            'CLIFramework' => 'vendor/corneltek/cliframework',
        ));
        $loader->addPrefix('CLIFramework\\', 'src/CLIFramework/');
    }

    public function testAddNamespace()
    {
        $loader = new \Universal\ClassLoader\SplClassLoader;
        ok( $loader );
        $loader->addNamespace('Foo', 'tests' . DIRECTORY_SEPARATOR . 'lib');
        $loader->register();

        $foo = new \Foo\Foo;
        ok( $foo );

        $bar = new \Foo\Bar;
        ok( $bar );

        $loader->unregister();
    }

    public function testReloadGuard()
    {
        require 'src/Universal/ClassLoader/SplClassLoader.php';
        require 'src/Universal/ClassLoader/SplClassLoader.php';
    }
}

