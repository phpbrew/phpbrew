<?php
namespace PhpBrew;

use PEARX\Utils as PEARXUtils;

class ExtensionMetaXml extends ExtensionMetaAbstraction implements ExtensionMetaInterface
{
    protected $meta;

    protected $peclInstallPath;

    public function __construct($packageXml, $peclInstallPath = null)
    {
        $this->meta = PEARXUtils::create_dom();

        if (false === $this->meta->loadXml(file_get_contents($packageXml))) {
            throw new \Exception("Error loading XMl document: {$packageXml}");
        }

        $this->peclInstallPath = $peclInstallPath;
    }

    public function isZend()
    {
        return (bool) $this->meta->getElementsByTagName('zendextsrcrelease')->length;
    }

    public function getName()
    {
        return str_replace('ext_', '', $this->meta->getElementsByTagName('name')->item(0)->nodeValue);
    }

    public function getRuntimeName()
    {
        $provides = $this->meta->getElementsByTagName('providesextension');

        return $provides->length ? $provides->item(0)->nodeValue : $this->getName();
    }

    public function getSourceFile()
    {
        return strtolower($this->getRuntimeName() . '.so');
    }

    public function getVersion()
    {
        return ($this->meta->getElementsByTagName('version')->item(0)
                           ->getElementsByTagName('release')->item(0)
                           ->nodeValue);
    }
}
