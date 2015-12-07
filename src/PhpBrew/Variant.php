<?php
namespace PhpBrew;

/**
 * The variant definition schema
 */
class Variant 
{
    public $name;

    public $desc;

    protected $packageMap;

    public $default;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function defaultOption($option)
    {
        $this->default = $option;
        return $this;
    }

    public function depends(array $packageMap)
    {
        $this->packageMap = $packageMap;
        return $this;
    }

    public function desc($description)
    {
        $this->desc = $description;
        return $this;
    }


    public function getPlatformPackages($platform)
    {
        if (isset($this->packageMap[$platform])) {
            return $this->packageMap[$platform];
        }
        return [];
    }

}

