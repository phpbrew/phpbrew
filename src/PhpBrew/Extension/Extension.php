<?php
namespace PhpBrew\Extension;
use PhpBrew\Config;

class Extension
{

    /**
     * @var string The extension package name
     *
     * The package name does not equal to the extension name.
     * for example, "APCu" provides "apcu" instead of "APCu"
     */
    protected $name;

    protected $extensionName;

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
        $this->extensionName = strtolower($name);
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

    public function setExtensionName($name)
    {
        $this->extensionName = $name;
    }

    public function getExtensionName()
    {
        return $this->extensionName;
    }


    public function setSourceDirectory($dir)
    {
        $this->sourceDirectory = $dir;
    }


    public function getSourceDirectory()
    {
        return $this->sourceDirectory;
    }

    public function getConfigFilePath()
    {
        return Config::getCurrentPhpConfigScanPath() . '/' . $this->getName() . '.ini';
    }
}

