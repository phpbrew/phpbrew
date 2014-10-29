<?php
namespace PhpBrew;
use CLIFramework\Logger;

class Extension implements ExtensionInterface
{

    /**
     * Extension meta
     * @var \PhpBrew\ExtensionMetaInterface
     */
    protected $meta;

    /**
     * Map of extensions that can't be enabled at the same time.
     * This helps phpbrew to unload antagonist extensions before enabling
     * an extension with a known conflict.
     * @var array
     */
    protected $conflicts = array (
        'json'  => array ('jsonc'),   // enabling jsonc disables json
        'jsonc' => array ('json'),    // enabling json disables jsonc
    );

    public function __construct($name)
    {
        Migrations::setupConfigFolder();
        $this->meta = $this->buildMetaFromName($name);
    }

    /**
     * Disable extensions known to conflict with current one
     */
    public function disableAntagonists()
    {
        $name = $this->meta->getName();
        if (isset($this->conflicts[$name])) {
            $conflicts = $this->conflicts[$name];
            $this->logger->info("===> Applying conflicts resolution (" . implode(', ', $conflicts) . "):");
            foreach ($conflicts as $extension) {
                $e = new Extension($extension, $this->logger);
                $e->disable();
            }
        }
    }

    public function hasConflicts()
    {
        return array_key_exists($this->meta->getName(), $this->conflicts);
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

    public function buildMetaFromName($name)
    {
        $path = Config::getBuildDir() . '/' . Config::getCurrentPhpName() . '/ext/'. $name;
        $xml = $path . '/package.xml';
        $m4 = $path . '/config.m4';

        if (file_exists($xml)) {
            // $this->logger->warning("===> Using xml extension meta");
            $meta = new ExtensionMetaXml($xml);
        } elseif (file_exists($m4)) {
            // $this->logger->warning("===> Using m4 extension meta");
            $meta = new ExtensionMetaM4($m4);
        } else {
            // $this->logger->warning("===> Using polyfill extension meta");
            $meta = new ExtensionMetaPolyfill($name);
        }
        return $meta;
    }

    public function getMeta()
    {
        return $this->meta;
    }

    public function setMeta($meta) {
        $this->meta = $meta;
    }
}
