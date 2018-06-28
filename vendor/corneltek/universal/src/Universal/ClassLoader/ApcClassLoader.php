<?php
namespace Universal\ClassLoader;
use Universal\ClassLoader\ClassLoader;
use Exception;

class ApcClassLoader extends SplClassLoader
{
    public $apcprefix = 'apc';

    public function __construct($prefix = '_apc', $namespaces = null)
    {
        parent::__construct( $namespaces );
        $this->apcPrefix = $prefix;
    }

    public function setApcPrefix($prefix)
    {
        $this->apcPrefix = $prefix;
    }

    public function loadClass($class)
    {
        if( ($file = apc_fetch($this->apcPrefix . $class) ) !== false ) {
            require $file;
            return true;
        }

        if ($file = $this->resolveClass($class)) {
            apc_store( $this->apcPrefix . $class , $file );
            require $file;
            return true;
        }
        return false;
    }
}

