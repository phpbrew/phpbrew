<?php
/*
 * This file is part of the GetOptionKit package.
 *
 * (c) Yo-An Lin <cornelius.howl@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */
namespace GetOptionKit;
use ArrayIterator;
use ArrayAccess;
use Iterator;
use IteratorAggregate;
use GetOptionKit\Argument;
use GetOptionKit\Option;

/**
 * Define the getopt parsing result
 *
 * create option result from array()
 *
 *     OptionResult::create($spec, array( 
 *         'key' => 'value'
 *     ), array( ... arguments ... ) );
 *
 */
class OptionResult 
    implements IteratorAggregate, ArrayAccess
{
    /**
     * @var array option specs, key => Option object 
     * */
    public $keys = array();

    private $currentKey;

    /* arguments */
    public $arguments = array();

    public function getIterator() {
        return new ArrayIterator($this->keys);
    }

    public function __isset($key)
    {
        return isset($this->keys[$key]);
    }

    public function __get($key)
    {
        if (isset($this->keys[$key])) {
            return $this->keys[$key]->getValue();
        }

        // verifying if we got a camelCased key: http://stackoverflow.com/a/7599674/102960
        //    get $options->baseDir as $option->{'base-dir'}
        $parts = preg_split('/(?<=[a-z])(?=[A-Z])|(?<=[A-Z])(?=[A-Z][a-z])/', $key);
        if (sizeof($parts) > 1) {
            $key = join('-', array_map('strtolower', $parts));
        }
        if (isset($this->keys[$key])) {
            return $this->keys[$key]->getValue();
        }
    }

    public function __set($key, $value)
    {
        $this->keys[ $key ] = $value;
    }

    public function has($key)
    {
        return isset($this->keys[ $key ]);
    }

    public function set($key, Option $value)
    {
        $this->keys[ $key ] = $value;
    }

    public function addArgument(Argument $arg)
    {
        $this->arguments[] = $arg;
    }

    public function getArguments()
    {
        return array_map( function($e) { return $e->__toString(); }, $this->arguments );
    }

    public function offsetSet($name,$value)
    {
        $this->keys[ $name ] = $value;
    }
    
    public function offsetExists($name)
    {
        return isset($this->keys[ $name ]);
    }
    
    public function offsetGet($name)
    {
        return $this->keys[ $name ];
    }
    
    public function offsetUnset($name)
    {
        unset($this->keys[$name]);
    }
    
    public function toArray()
    {
        $array = array();
        foreach ($this->keys as $key => $option ) {
            $array[ $key ] = $option->getValue();
        }
        return $array;
    }

    static function create($specs, array $values = array(), array $arguments = null)
    {
        $new = new self;
        foreach ($specs as $spec) {
            $id = $spec->getId();
            if (isset($values[$id])) {
                $new->$id = $spec;
                $spec->setValue( $values[$id] );
            }
            if ($arguments) {
                foreach ($arguments as $arg){
                    $new->addArgument( new Argument( $arg ) );
                }
            }
        }
        return $new;
    }

}

