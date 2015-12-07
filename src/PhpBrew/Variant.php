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


    public $optionName;

    public $option;

    public $defaultOption;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function optionName($optionName)
    {
        $this->optionName = $optionName;
        return $this;
    }

    public function option($option)
    {
        $this->option = $option;
        return $this;
    }

    public function defaultOption($option)
    {
        $this->defaultOption = $option;
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

    public function platformPackages($platform, array $packages)
    {
        $this->packageMap[$platform] = $packages;
        return $this;
    }

    public function getPlatformPackages($platform)
    {
        if (isset($this->packageMap[$platform])) {
            return $this->packageMap[$platform];
        }
        return array();
    }

    public function disableDefaultOption()
    {
        $this->defaultOption = null;
    }

    public function toArgument()
    {
        if ($this->optionName) {
            $str = $this->optionName;
            // TODO: escape spaces here
            if ($this->option) {
                $str .= '=' . $this->option;
            } else if ($this->defaultOption) {
                $str .= '=' . $this->defaultOption;
            }
            return $str;
        }
        return null;
    }


}

