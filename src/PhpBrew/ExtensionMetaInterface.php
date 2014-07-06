<?php
namespace PhpBrew;

interface ExtensionMetaInterface
{
    public function isZend();
    public function getName();
    public function getRuntimeName();
    public function getSourceFile();
    public function getIniFile();
    public function getPath();
    public function getVersion();
}
