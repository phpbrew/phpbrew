<?php 
namespace Universal\Http;
use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use SplFileInfo;

class FilesParameter extends Parameter
        implements ArrayAccess, IteratorAggregate
{
    public function __construct($hash = null)
    {
        if ($hash) {
            $this->hash = $hash;
        } else if (isset($_FILES)) {
            $this->hash = self::fix_files_array($_FILES);
        }
    }

    public function getIterator()
    {
        return new ArrayIterator($this->hash);
    }
    
    public function offsetSet($name,$value)
    {
        $this->hash[ $name ] = $value;
    }
    
    public function offsetExists($name)
    {
        return isset($this->hash[ $name ]);
    }
    
    public function offsetGet($name)
    {
        return $this->hash[ $name ];
    }
    
    public function offsetUnset($name)
    {
        unset($this->hash[$name]);
    }

    public static function _move_indexes_right($files) {
        if( ! is_array($files['name']) )
            return $files;
        $results = array(); 
        foreach($files['name'] as $index => $name) { 
            $reordered = array( 
                'name' => $files['name'][$index], 
                'tmp_name' => $files['tmp_name'][$index], 
                'size' => $files['size'][$index], 
                'type' => $files['type'][$index], 
                'error' => $files['error'][$index], 
            ); 
            
            // If this is not leaf do it recursivly 
            if (is_array($name))  {
                $reordered = FilesParameter::_move_indexes_right($reordered); 
            }
            $results[$index] = $reordered; 
        } 
        return $results; 
    }

    public static function fix_files_array($files)
    {
        // Multiple values for post-keys indexes 
        if (isset($files['name'], $files['tmp_name'], $files['size'], $files['type'], $files['error'])){ 
            return FilesParameter::_move_indexes_right($files); 
        }
        // Re order pre-keys indexes            
        array_walk($files, function(&$sub) {
            if (is_array($sub)) {
                $sub = FilesParameter::fix_files_array($sub); 
            }
        });
        return $files;
    }
}
