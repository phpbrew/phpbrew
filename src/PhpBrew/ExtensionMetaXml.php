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
        return $this->meta->getElementsByTagName('name')->item(0)->nodeValue;
    }

    public function getSourceFile()
    {
        $provides = $this->meta->getElementsByTagName('providesextension');
        $provides->length ? $source = $provides->item(0)->nodeValue : $source = $this->getName();
        $source .= '.so';

        return strtolower($source);
    }

    public function getVersion()
    {
        return ($this->meta->getElementsByTagName('version')->item(0)
                           ->getElementsByTagName('release')->item(0)
                           ->nodeValue);
    }
}
