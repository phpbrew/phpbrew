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
use GetOptionKit\OptionCollection;

class Argument 
{
    public $arg;

    public function __construct($arg)
    {
        $this->arg = $arg;
    }


    public function isLongOption()
    {
        return substr($this->arg,0,2) === '--';
    }

    public function isShortOption()
    {
        return (substr($this->arg,0,1) === '-' ) 
            && (substr($this->arg,1,1) !== '-');
    }

    public function isEmpty()
    {
        return $this->arg === null || empty($this->arg) && ('0' !== $this->arg);
    }


    /**
     * Check if an option is one of the option in the collection
     */
    public function anyOfOptions(OptionCollection $options)
    {
        $name = $this->getOptionName();
        $keys = $options->keys();
        return in_array($name, $keys);
    }


    /**
     * Check current argument is an option by the preceding dash.
     * note this method does not work for string with negative value.
     *
     *   -a
     *   --foo
     */
    public function isOption()
    {
        return $this->isShortOption() || $this->isLongOption();
    }


    public function getOption() {
        if (preg_match('/^([-]+[a-zA-Z0-9-]+)/', $this->arg, $regs)) {
            return $regs[1];
        }
    }


    /**
     * Parse option and return the name after dash. e.g., 
     * '--foo' returns 'foo'
     * '-f' returns 'f'
     *
     * @return string
     */
    public function getOptionName()
    {
        if (preg_match('/^[-]+([a-zA-Z0-9-]+)/',$this->arg,$regs)) {
            return $regs[1];
        }
    }

    public function splitAsOption() {
        return explode('=', $this->arg, 2);
    }

    public function containsOptionValue()
    {
        return preg_match('/=.+/',$this->arg);
    }

    public function getOptionValue()
    {
        if (preg_match('/=(.+)/',$this->arg,$regs)) {
            return $regs[1];
        }
    }

    /** 
     * Check combined short flags for "-abc" or "-vvv"
     *
     * like: -abc
     */
    public function withExtraFlagOptions()
    {
        return preg_match('/^-[a-zA-Z0-9]{2,}/',$this->arg);
    }

    public function extractExtraFlagOptions()
    {
        $args = array();
        for($i=2;$i< strlen($this->arg); ++$i) {
            $args[] = '-' . $this->arg[$i];
        }
        $this->arg = substr($this->arg,0,2); # -[a-z]
        return $args;
    }

    public function __toString()
    {
        return $this->arg;
    }

}



