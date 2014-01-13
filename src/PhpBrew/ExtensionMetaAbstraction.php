<?php

namespace PhpBrew;

use PhpBrew\Config;

abstract class ExtensionMetaAbstraction implements ExtensionMetaInterface
{

    public function getIniFile()
    {
        return Config::getCurrentPhpConfigScanPath() . '/' . $this->getName() . '.ini';
    }

    public function getPath()
    {
        return Config::getBuildDir() . '/' . Config::getCurrentPhpName() . '/ext/' . $this->getName();
    }

}
