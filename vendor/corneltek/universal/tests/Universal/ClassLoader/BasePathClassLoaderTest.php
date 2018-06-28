<?php 

namespace Universal\ClassLoader;
use PHPUnit_Framework_TestCase;
use Exception;

class BasePathClassLoaderTest extends PHPUnit_Framework_TestCase
{
    function testFunc()
    {
        $loader = new BasePathClassLoader( array( 
            'tests/lib'
        ));
        $loader->register();
        ok( $loader );

        spl_autoload_call( 'Foo\Bar' );
        ok( class_exists( 'Foo\Bar' ) );

        $loader->unregister();
    }
}


