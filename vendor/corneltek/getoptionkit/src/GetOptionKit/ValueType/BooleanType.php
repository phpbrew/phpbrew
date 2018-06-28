<?php
namespace GetOptionKit\ValueType;

class BooleanType extends BaseType
{
    public function test($value) { 
        if (is_string($value) ) {
            $value = strtolower($value);
            return ('0' == $value || '1' == $value || 'true' == $value || 'false' == $value);
        }
        return is_bool($value);
    }

    public function parse($value) {
        $value = strtolower($value);
        if ($value == '0' || $value == 'false')
            return false;
        if ($value == '1' || $value == 'true')
            return true;
        return false;
    }

}



