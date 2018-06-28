<?php
namespace GetOptionKit\ValueType;

class EmailType extends BaseType
{
    public function test($value) {
        return filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    public function parse($value) {
        return strval($value);
    }
}