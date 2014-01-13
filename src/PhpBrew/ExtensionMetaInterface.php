<?php

namespace PhpBrew;

use PhpBrew\Config;
use PhpBrew\ExtensionInstaller;
use PhpBrew\ExtensionInterface;
use PEARX\Utils as PEARXUtils;

interface ExtensionMetaInterface
{
    public function isZend();
    public function getName();
    public function getSourceFile();
    public function getIniFile();
    public function getPath();
    public function getVersion();
}