<?php
namespace CacheKit;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use SerializerKit;

class FileSystemCache
 implements CacheInterface
{
    public $expiry; // seconds

    public $filenameBuilder;

    public $serializer;

    public $cacheDir;
    
    public function __construct($options = array() )
    {
        if ( isset($options['expiry']) ) {
            $this->expiry = $options['expiry'];
        }

        if ( isset($options['cache_dir']) ) {
            $this->cacheDir = $options['cache_dir'];
        } else {
            $this->cacheDir = 'cache';
        }

        if ( isset($options['serializer']) ) {
            $this->serializer = $options['serializer'];
        }

        if ( ! file_exists($this->cacheDir) ) {
            mkdir($this->cacheDir, 0755, true );
        }

        $this->filenameBuilder = function($key) {
            return preg_replace('#\W+#','_',$key);
        };
    }

    public function _getCacheFilepath($key)
    {
        // $filename = call_user_func($this->filenameBuilder,$key);
        $filename = preg_replace('#\W+#','_',$key);
        return $this->cacheDir . DIRECTORY_SEPARATOR . $filename;
    }

    public function _decodeFile($file) 
    {
        $content = file_get_contents($file);
        if( $this->serializer )
            return $this->serializer->decode( $content );
        return $content;
    }

    public function _encodeFile($file,$data)
    {
        $content = null;
        if( $this->serializer )
            $content = $this->serializer->encode( $data );
        else
            $content = $data;
        return file_put_contents( $file, $content );
    }

    public function __get($key)
    {
        return $this->get($key);
    }

    public function __set($key,$val)
    {
        return $this->set($key,$val);
    }

    public function get($key) 
    {
        $path = $this->_getCacheFilepath($key);

        if( ! file_exists($path) )
            return null;

        // is expired ?
        if( $this->expiry && (time() - filemtime($path)) > $this->expiry ) {
            return null;
        }

        return $this->_decodeFile($path);
    }

    public function set($key,$value,$ttl = 0) 
    {
        $path = $this->_getCacheFilepath($key);
        return $this->_encodeFile($path,$value) !== false;
    }

    public function remove($key) 
    {
        $path = $this->_getCacheFilepath($key);
        if( file_exists($path) )
            unlink( $path );
    }

    public function clear() 
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->cacheDir),
                                                RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $path) {
            if( $path->isFile() ) {
                unlink( $path->__toString() );
            }
        }
    }
}





