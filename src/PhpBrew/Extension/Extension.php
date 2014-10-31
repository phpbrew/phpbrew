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
     * @var string config.m4 filename
     */
    protected $configM4File = 'config.m4';

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
        if ($this->sharedLibraryName) {
            return $this->sharedLibraryName;
        }
        return strtolower($this->extensionName) . '.so'; // for windows it might be a DLL.
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

        if ($configM4File = $this->findConfigM4File($dir)) {
            $this->configM4File = $configM4File;
        } else {
            throw new Exception('config[0-9]?.m4 file not found.');
        }
    }

    public function getConfigM4File() {
        return $this->configM4File;
    }

    public function findConfigM4File($dir) {
        $configM4Path = $dir . DIRECTORY_SEPARATOR . 'config.m4';
        if (file_exists($configM4Path)) {
            return 'config.m4';
        }

        for ($i = 0 ; $i < 10 ; $i++ ) {
            $filename = "config{$i}.m4";
            $configM4Path = $dir . DIRECTORY_SEPARATOR . $filename;
            if (file_exists($configM4Path)) {
                return $filename;
            }
        }
    }

    public function getSourceDirectory()
    {
        return $this->sourceDirectory;
    }


    public function getSharedLibraryPath() {
        return ini_get('extension_dir') . DIRECTORY_SEPARATOR . $this->getSharedLibraryName();
    }

    public function getConfigFilePath()
    {
        return Config::getCurrentPhpConfigScanPath() . '/' . $this->getName() . '.ini';
    }

    /**
     * Checks if current extension is loaded
     *
     * @return boolean
     */
    public function isLoaded()
    {
        return extension_loaded($this->extensionName);
    }

    /**
     * Checks if extension.so file is in place
     *
     * @return boolean
     */
    public function isInstalled()
    {
        return file_exists($this->getSharedLibraryPath());
    }
}

