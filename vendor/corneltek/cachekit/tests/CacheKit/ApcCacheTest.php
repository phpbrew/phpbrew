<?php

class ApcCacheTest extends PHPUnit_Framework_TestCase
{
    public function test()
    {

        if ( ! extension_loaded('apc') ) {
            skip('apc extension is required.');
        }



        $cache = new CacheKit\ApcCache(array( 
            'namespace' => 'app_'
        ));
        ok($cache);
        $cache->set('key','val1');
        $cache->get('key');
    }
}

