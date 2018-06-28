<?php


namespace CacheKit;
use PHPUnit_Framework_TestCase;

class CacheKitTest extends PHPUnit_Framework_TestCase
{
    function test()
    {
        $c = new CacheKit;
        ok( $c );

        $memory = $c->createBackend( 'MemoryCache' );
        ok( $memory );

        $memory->set( 'foo' , '123' );

        ok( $memory->get('foo') );
    }
}



