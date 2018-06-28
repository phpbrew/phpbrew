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
use GetOptionKit\Option;
use Iterator;
use ArrayIterator;
use IteratorAggregate;
use Countable;

class OptionCollection
    implements IteratorAggregate, Countable
{
    public $data = array();

    /**
     * @var Option[string]
     *
     * read-only property
     */
    public $longOptions = array();

    /**
     * @var Option[string]
     *
     * read-only property
     */
    public $shortOptions = array();

    /**
     * @var Option[]
     *
     * read-only property
     */
    public $options = array();



    public function __construct()
    {
        $this->data = array();
    }

    public function __clone()
    {
        foreach( $this->data as $k => $v ) {
            $this->data[ $k ] = clone $v;
        }
        foreach( $this->longOptions as $k => $v ) {
            $this->longOptions[ $k ] = clone $v;
        }
        foreach( $this->shortOptions as $k => $v ) {
            $this->shortOptions[ $k ] = clone $v;
        }
        foreach( $this->options as $k => $v ) {
            $this->options[ $k ] = clone $v;
        }
    }

    /**
     * add( [spec string], [desc string] )
     *
     * add( [option object] )
     */
    public function add()
    {
        $num = func_num_args();
        $args = func_get_args();
        $first = $args[0];

        if (is_object($first) && $first instanceof Option) {
            $this->addObject( $first );
        } else if ( is_string( $first ) ) {
            $specString  = $args[0];
            $desc = isset($args[1]) ? $args[1] : null;
            $key         = isset($args[2]) ? $args[2] : null;

            // parse spec string
            $spec = new Option($specString);
            if( $desc )
                $spec->desc($desc);
            if( $key )
                $spec->key = $key;
            $this->add( $spec );
            return $spec;
        } else {
            throw new Exception( 'Unknown Spec Type' );
        }
    }

    /**
     * Add option object
     *
     * @param Object $spec the option object.
     */
    public function addObject( Option $spec )
    {
        $this->data[ $spec->getId() ] = $spec;
        if( $spec->long )
            $this->longOptions[ $spec->long ] = $spec;
        if( $spec->short )
            $this->shortOptions[ $spec->short ] = $spec;
        $this->options[] = $spec;
        if( ! $spec->long && ! $spec->short )
            throw new Exception('Wrong option spec');
    }

    public function getLongOption( $name )
    {
        return isset($this->longOptions[ $name ]) ? $this->longOptions[ $name ] : null;
    }

    public function getShortOption( $name )
    {
        return isset($this->shortOptions[ $name ]) ? $this->shortOptions[ $name ] : null;
    }

    /* Get spec by spec id */
    public function get($id)
    {
        if (isset($this->data[$id])) {
            return $this->data[$id];
        } elseif (isset($this->longOptions[$id])) {
            return $this->longOptions[$id];
        } elseif (isset($this->shortOptions[$id])) {
            return $this->shortOptions[$id];
        }
    }


    public function find($name) {
        foreach( $this->options as $option ) {
            if ($option->short == $name || $option->long == $name) {
                return $option;
            }
        }
    }

    public function size()
    {
        return count($this->data);
    }

    public function all()
    {
        return $this->data;
    }

    public function toArray()
    {
        $array = array();
        foreach($this->data as $k => $spec) {
            $item = array();
            if ($spec->long) {
                $item['long'] = $spec->long;
            }
            if ($spec->short ) {
                $item['short'] = $spec->short;
            }
            $item['desc'] = $spec->desc;
            $array[] = $item;
        }
        return $array;
    }

    public function keys()
    {
        return array_merge(array_keys($this->longOptions), array_keys($this->shortOptions));
    }

    public function count() {
        return count($this->data);
    }


    public function getIterator() {
        return new ArrayIterator($this->data);
    }
}
