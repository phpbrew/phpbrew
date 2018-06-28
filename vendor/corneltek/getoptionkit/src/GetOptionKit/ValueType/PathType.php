<?php
namespace GetOptionKit\ValueType;
use SplFileInfo;

class PathType extends BaseType
{
    public function test($value)
    {
        return file_exists($value);
    }

    public function parse($value)
    {
        return new SplFileInfo($value);
    }
}


