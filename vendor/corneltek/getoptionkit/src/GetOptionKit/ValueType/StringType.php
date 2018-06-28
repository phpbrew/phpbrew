<?php
namespace GetOptionKit\ValueType;

class StringType extends BaseType
{
    public function test($value) { 
        return is_string($value);
    }

    public function parse($value) {
        return strval($value);
    }
}


