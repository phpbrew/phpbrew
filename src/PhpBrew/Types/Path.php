<?php
namespace PhpBrew\Types;

use SplFileInfo;

class Path
{
    protected $path;

    public function __construct($path)
    {
        $this->path = $path;
    }

    public function getSplFileInfo()
    {
        return new SplFileInfo($this->path);
    }

    public function __toString()
    {
        return $this->path;
    }
}
