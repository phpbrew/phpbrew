<?php
namespace GetOptionKit\ValueType;
use SplFileInfo;

class FileType extends BaseType
{
    public function test($value) {
        return is_file($value);
    }

    public function parse($value) {
        return new SplFileInfo($value);
    }
}


