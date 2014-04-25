<?php

namespace PhpBrew;

use PEARX\Utils as PEARXUtils;

class ExtensionMetaPolyfill  extends ExtensionMetaAbstraction implements ExtensionMetaInterface
{

    protected $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function isZend()
    {
    }

    public function getName()
    {
        return $this->name;
    }

    public function getVersion()
    {
        return null;
    }
}
