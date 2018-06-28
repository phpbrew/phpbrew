<?php
namespace GetOptionKit\ValueType;
use SplFileInfo;

class DirType extends BaseType
{
    public function test($value) 
    {
        return is_dir($value);
    }

    public function parse($value) 
    {
        return new SplFileInfo($value);
    }
}


