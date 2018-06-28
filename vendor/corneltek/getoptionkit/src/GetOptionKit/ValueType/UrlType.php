<?php
namespace GetOptionKit\ValueType;

class UrlType extends BaseType
{
    public function test($value) {
        return filter_var($value, FILTER_VALIDATE_URL);
    }

    public function parse($value) {
        return strval($value);
    }
}