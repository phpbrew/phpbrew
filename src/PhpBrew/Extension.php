<?php
namespace PhpBrew;
use CLIFramework\Logger;
use PhpBrew\Extension\ExtensionFactory;
use PhpBrew\ExtensionMetaInterface;

class Extension
{

    /**
     * Extension meta
     * @var \PhpBrew\ExtensionMetaInterface
     */
    protected $meta;

    public function __construct($name, ExtensionMetaInterface $meta)
    {
        Migrations::setupConfigFolder();
        $this->meta = $meta;
    }

    /**
     * Checks if current extension is loaded
     *
     * @return boolean
     */
    public function isLoaded()
    {
        return extension_loaded($this->meta->getRuntimeName());
    }

    /**
     * Checks if extension.so file is in place
     *
     * @return boolean
     */
    public function isInstalled()
    {
        return file_exists(ini_get('extension_dir') . '/' . $this->meta->getSourceFile());
    }

    /**
     * Checks if current extension source is available for local install
     * @return boolean
     */
    public function isAvailable()
    {
        foreach (glob($this->meta->getPath() . '/*', GLOB_ONLYDIR) as $available_extension) {
            if (false !== strpos(basename($available_extension), $this->meta->getName())) {
                return true;
            }
        }

        return false;
    }

    public function purge()
    {
        $ini = $this->meta->getIniFile();
        unlink($ini);
        unlink($ini . '.disabled');
    }

    public function getMeta()
    {
        return $this->meta;
    }

    public function setMeta($meta) {
        $this->meta = $meta;
    }
}
