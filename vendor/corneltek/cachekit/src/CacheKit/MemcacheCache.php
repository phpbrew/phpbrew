<?php
/*
 * This file is part of the CacheKit package.
 *
 * (c) Yo-An Lin <cornelius.howl@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */
namespace CacheKit;

use Memcache;

class MemcacheCache 
    implements CacheInterface
{
    private $handle;
    public $compress = false;

    public function __construct($servers = array() )
    {
        $this->handle = new Memcache;
        foreach( $servers as $server ) {
            $this->handle->connect( $server[0] , $server[1] ) 
                or die ("Could not connect");
        }
    }

    public function __get($key)
    {
        return $this->get($key);
    }

    public function __set($key,$val)
    {
        return $this->set($key,$val);
    }

    public function set($key,$value,$ttl = 0)
    {
        $this->handle->set( $key , serialize( $value ) , $this->compress , $ttl );
    }

    public function get($key)
    {
        $v = $this->handle->get( $key );
        if ( $v ) {
            return unserialize($v);
        }
    }

    public function remove($key)
    {
        $this->handle->delete($key);
    }

    public function clear()
    {
        $this->handle->flush();
    }

    static function getInstance()
    {
        static $instance;
        return $instance ? $instance : $instance = new static;
    }

}

