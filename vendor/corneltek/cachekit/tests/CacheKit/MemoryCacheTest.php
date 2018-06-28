<?php

class MemoryCacheTest extends PHPUnit_Framework_TestCase
{
    function test()
    {
        $cache = new CacheKit\MemoryCache;
        ok($cache);

        $cache->set('foo','bar');
        $bar = $cache->get('foo');
        ok($bar);
    }
}

