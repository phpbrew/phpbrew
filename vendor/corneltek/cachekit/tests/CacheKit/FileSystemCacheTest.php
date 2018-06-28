<?php

class FileSystemCacheTest extends PHPUnit_Framework_TestCase
{

    function testSerializer()
    {
        $serializer = new SerializerKit\Serializer('json');
        $cache = new CacheKit\FileSystemCache(array( 
            'expiry' => 30,
            'cache_dir' => 'cache',
            'serializer' => $serializer,
        ));
        $cache->clear();

        $url = 'foo_bar';
        $html = 'test content';
        $cache->set( $url , $html );
        $html2 = $cache->get( $url );

        is( $html , $html2 );

        $cache->remove( $url );

        ok( null === $cache->get( $url ) );

        $cache->clear();

        ok( null === $cache->get( $url ) );
    }

    public function test()
    {
        $cache = new CacheKit\FileSystemCache(array( 
            'expiry' => 30,
            'cache_dir' => 'cache',
        ));

        $url = 'http://google.com';
        $cache->set( $url , $html = 'blah' );
        $html2 = $cache->get( $url );

        is( $html , $html2 );
        $cache->remove( $url );
        ok( null === $cache->get( $url ) );

        $cache->clear();
    }
}

