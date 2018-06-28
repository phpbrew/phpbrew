<?php
namespace GetOptionKit\ValueType;

class DateType extends BaseType
{
    public function test($value) {
        return date_parse($value) !== FALSE ? TRUE : FALSE;
    }

    public function parse($value) {
        return date_parse($value);
    }
}

