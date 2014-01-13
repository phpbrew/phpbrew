<?php

namespace PhpBrew;

use PEARX\Utils as PEARXUtils;

class ExtensionMetaM4 extends ExtensionMetaAbstraction implements ExtensionMetaInterface
{

    protected $meta;

    public function __construct($m4)
    {
        if(!file_exists($m4)) {
            throw new \Exception("Error loading m4 file: {$m4}");
        }
        $this->meta = file_get_contents($m4);
        preg_match_all('#(?<=PHP_NEW_EXTENSION\()\w+#s', $this->meta, $matches);
        $this->name = $matches[0][0];
    }

    /**
     * @todo How to know if extension is zend when using config.m4 meta?
     */
    public function isZend()
    {
    }

    public function getName()
    {
        return $this->name;
    }

    public function getSourceFile()
    {
        return $this->name . '.so';
    }

    public function getVersion()
    {
        return null;
    }
}
