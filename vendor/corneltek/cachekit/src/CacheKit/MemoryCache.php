<?php
namespace CacheKit;

class MemoryCache
    implements CacheInterface
{
    private $_cache = array();

    public function get($key)
    {
        if ( isset($this->_cache[ $key ] ) )
            return $this->_cache[ $key ];
    }

    public function set($key,$value,$ttl = 0)
    {
        $this->_cache[ $key ] = $value;
    }

    public function __set($key,$value)
    {
        $this->set($key,$value);
    }

    public function __get($key)
    {
        return $this->get($key);
    }

    public function remove($key)
    {
        unset( $this->_cache[ $key ] );
    }

    public function clear()
    {
        $this->_cache = array();
    }

    static function getInstance()
    {
        static $instance;
        return $instance ? $instance : $instance = new static;
    }

}
