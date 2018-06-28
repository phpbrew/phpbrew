<?php
/*
 * This file is part of the Universal package.
 *
 * (c) Yo-An Lin <cornelius.howl@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */
namespace Universal\Event;

/**
 * A Simple PHP Event dispatcher
 *
 *
 * TODO: Move this PhpEvent to a standard-alone component.
 * TODO: implement an extension for this.
 */
class PhpEvent
{
    /**
     * Event pool
     *
     * @var array save event callbacks
     */
    public $eventPool = array();

    /**
     * Register event name
     *
     * @param string $ev
     * @param closure $cb callable function
     */
    public function register($ev,$cb)
    {
        if (! isset($this->eventPool[ $ev ] )) {
            $this->eventPool[ $ev ] = array();
        }
        $this->eventPool[ $ev ][] = $cb;
    }

    /**
     * This is an alias of register method.
     *
     * @param string $ev
     * @param closure $cb callable function
     */
    public function bind($ev,$cb)
    {
        if (! isset($this->eventPool[ $ev ] )) {
            $this->eventPool[ $ev ] = array();
        }
        $this->eventPool[ $ev ][] = $cb;
    }




    /**
     * Trigger event with event name
     *
     * @param string $evname event name
     * @param mixed extra parameters
     */
    public function trigger($evname)
    {
        $results = array();
        if( isset( $this->eventPool[ $evname ] ) ) {
            $args = func_get_args();
            array_shift( $args );
            foreach( $this->eventPool[ $evname ] as $cb ) {
                /**
                 * to break the event trigger, just return false.
                 */
                if( ($ret = call_user_func_array( $cb , $args )) === false ) 
                    break;
                $results[] = $ret;
            }
        }
        return $results;
    }


    /**
     * clear event pool
     */
    public function clear()
    {
        // clear event pool
        $this->eventPool = array();
    }


    /**
     * static singleton method
     */
    static function getInstance()
    {
        static $instance;
        return $instance ? $instance : $instance = new static;
    }

}
