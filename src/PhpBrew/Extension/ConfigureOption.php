<?php
namespace PhpBrew\Extension;

class ConfigureOption
{
    public $option;

    public $desc;

    public $valueHint;

    public function __construct($option, $desc, $valueHint = NULL)
    {
        $this->option = $option;
        $this->desc = $desc;
        $this->valueHint = $valueHint;
    }
}



