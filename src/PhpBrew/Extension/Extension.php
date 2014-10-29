<?php
namespace PhpBrew\Extension;

class Extension
{

    /**
     * The extension name
     */
    protected $name;

    protected $version;

    /**
     * The extension so name
     */
    protected $sharedLibraryName;

    protected $sourceDirectory;

    protected $isZend;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getName() 
    {
        return $this->name;
    }

    public function setVersion($version)
    {
        $this->version = $version;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function setZend($zendExtension = true)
    {
        $this->isZend = $zendExtension;
    }

    public function isZend()
    {
        return $this->isZend;
    }

    public function setSharedLibraryName($n)
    {
        $this->sharedLibraryName = $n;
    }

    public function getSharedLibraryName()
    {
        return $this->sharedLibraryName;
    }

    public function setSourceDirectory($dir)
    {
        $this->sourceDirectory = $dir;
    }

}

