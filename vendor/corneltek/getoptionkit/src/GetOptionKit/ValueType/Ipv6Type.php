<?php
namespace GetOptionKit\ValueType;

class Ipv6Type extends BaseType
{
    public function test($value) {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
    }

    public function parse($value) {
        return strval($value);
    }
}
