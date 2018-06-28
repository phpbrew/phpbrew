<?php 
namespace Universal\Http;
use ArrayAccess;

class Parameter implements ArrayAccess
{
    public $hash = array();

    public function __construct( & $hash = array() )
    {
        $this->hash = $hash;
    }

    public function has( $name )
    {
        return isset( $this->hash[ $name ] );
    }

    public function __isset( $name )
    {
        return isset( $this->hash[ $name ] );
    }

    public function __get( $name )
    {
        if( isset($this->hash[$name]) ) {
            return $this->hash[ $name ];
        }
    }

    public function __set( $name , $value )
    {
        $this->hash[ $name ] = $value;
    }


    public function offsetGet($key)
    {
        return $this->hash[ $key ];
    }

    public function offsetSet($key,$value)
    {
        $this->hash[ $key ] = $value;
    }

    public function offsetExists($key)
    {
        return isset( $this->hash[ $key ] );
    }

    public function offsetUnset($key)
    {
        unset( $this->hash[ $key ] );
    }

}
