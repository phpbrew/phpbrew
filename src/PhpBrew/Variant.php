<?php
namespace PhpBrew;

use PhpBrew\Build;

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

    /**
     * callable option builder
     */
    protected $builder;

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


    public function builder($builder)
    {
        $this->builder = $builder;
        return $this;
    }



    public function toArguments(Build $build = null)
    {
        $options = array();
        if ($this->optionName) {
            $str = $this->optionName;

            // TODO: escape spaces here
            $opt = null;
            if ($this->option) {
                $opt = $this->option;
            } elseif ($this->defaultOption) {
                $opt = $this->defaultOption;
            }
            if ($opt) {
                if (is_callable($opt)) {
                    $opt = call_user_func($opt, $build);
                }
                if ($opt) {
                    $str .= '=' . $opt;
                }
            }
            $options[] = $str;
        }

        if ($this->builder) {
            if ($opts = call_user_func($this->builder)) {
                $options = array_merge($options, $opts);
            }
        }
        return $options;
    }
}

