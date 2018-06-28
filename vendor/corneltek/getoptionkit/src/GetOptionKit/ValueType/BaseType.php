<?php
namespace GetOptionKit\ValueType;

abstract class BaseType
{
    /**
     * Type option
     * 
     * @var mixed
     */
    public $option;

    public function __construct($option = null)
    {
        if($option) $this->option = $option;
    }

    /**
     * Test a value to see if it fit the type
     *
     * @param mixed $value
     */
    abstract public function test($value);

    /**
     * Parse a string value into it's type value
     *
     * @param mixed $value
     */
    abstract public function parse($value);
}