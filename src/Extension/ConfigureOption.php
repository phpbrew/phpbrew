<?php

namespace PHPBrew\Extension;

class ConfigureOption
{
    public $option;

    public $desc;

    public $valueHint;

    public $defaultValue;

    public function __construct($option, $desc, $valueHint = null)
    {
        $this->option = $option;
        $this->desc = $desc;
        $this->valueHint = $valueHint;
    }

    public function getOption()
    {
        return $this->option;
    }

    public function getDescription()
    {
        return $this->desc;
    }

    public function getValueHint()
    {
        return $this->valueHint;
    }

    public function setDefaultValue($value)
    {
        $this->defaultValue = $value;
    }
}
