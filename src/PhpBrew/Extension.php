<?php

namespace PhpBrew;

use PhpBrew\Migrations;
use PhpBrew\Config;
use PhpBrew\ExtensionInstaller;
use PhpBrew\ExtensionInterface;
use PEARX\Utils as PEARXUtils;

class Extension implements ExtensionInterface
{

    /**
     * Extension meta
     * @var PhpBrew\ExtensionMetaInterface
     */
    protected $meta;

    /**
     * Application logger
     */
    protected $logger;

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

    public function __construct($name, $logger)
    {
        Migrations::setupConfigFolder();
        $this->logger = $logger;
        $this->meta = $this->buildMetaFromName($name);
    }

    public function install($version = 'stable', array $options = array())
    {
        $this->logger->quiet();
        $this->disable();
        $this->logger->setLevel(4);

        $installer = new ExtensionInstaller($this->logger);
        $path = $this->meta->getPath();
        $name = $this->meta->getName();

        // Install local extension
        if ( file_exists( $path ) ) { 
            $this->logger->info("===> Installing {$name} extension...");
            $this->logger->debug("Extension path $path");
            $xml = $installer->runInstall($name, $path, $options);
        } else {
            chdir(dirname($path));
            $xml = $installer->installFromPecl($name, $version ,$options);
        }

        // try to rebuild meta from xml, which is more accurate right now
        if(file_exists($xml)) {
            $this->logger->warning("===> switching to xml meta");
            $this->meta = new ExtensionMetaXml($xml);
        }

        $ini = $this->meta->getIniFile() . '.disabled';
        $this->logger->info("===> Creating config file {$ini}");

        // create extension config file
        if ( file_exists($ini) ) {
            $lines = file($ini);
            foreach ($lines as &$line) {
                if ( preg_match('#^;\s*((?:zend_)?extension\s*=.*)#', $line, $regs ) ) {
                    $line = $regs[1];
                }
            }
            file_put_contents($ini, join('', $lines) );
        } else {
            $this->meta->isZend() ? $content = "zend_extension=" :  $content = "extension=";
            file_put_contents($ini, $content . $this->meta->getSourceFile() );
            $this->logger->debug("{$ini} is created.");
        }
        $this->logger->info("===> Enabling extension...");
        $this->enable();
        $this->logger->info("Done.");

        return $path;
    }

    /**
     * Enables ini file for current extension
     * @return boolean
     */
    public function enable()
    {
        $name = $this->meta->getName();
        $enabled_file = $this->meta->getIniFile();
        $disabled_file = $enabled_file . '.disabled';

        if (file_exists($enabled_file)) {
            $this->logger->info("[*] {$name} extension is already enabled.");

            return true;
        }

        if (file_exists($disabled_file)) {
            $this->disableAntagonists();
            if ( rename($disabled_file, $enabled_file) ) {
                $this->logger->info("[*] {$name} extension is enabled.");

                return true;
            }
            $this->logger->warning("failed to enable {$name} extension.");
        }

        $this->logger->info("{$name} extension is not installed. Suggestions:");
        $this->logger->info("\t\$ phpbrew ext install {$name}");

        return false;
    }

    /**
     * Disables ini file for current extension
     * @return boolean
     */
    public function disable()
    {
        $name = $this->meta->getName();
        $enabled_file = $this->meta->getIniFile();
        $disabled_file = $enabled_file . '.disabled';

        if (file_exists($disabled_file)) {
            $this->logger->info("[ ] {$name} extension is already disabled.");

            return true;
        }

        if (file_exists($enabled_file)) {
            if (rename($enabled_file, $disabled_file)) {
                $this->logger->info("[ ] {$name} extension is disabled.");

                return true;
            }
            $this->logger->warning("failed to disable {$name} extension.");
        }

        return false;
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
                $extension = new Extension($extension, $this->logger);
                $extension->disable();
            }
        }
    }


    /**
     * Checks if current extension is loaded
     * @return boolean
     */
    public function isLoaded()
    {
        return extension_loaded($this->meta->getName());
    }

    /**
     * Checks if current extension is available for local install
     * @return boolean
     */
    public function isAvailable()
    {
        foreach (glob($this->meta->getPath() . '/*', GLOB_ONLYDIR) as $available_extension) {
            if (false !== strpos(basename($available_extension), $name)) {
                return true;
            }
        }

        return false;
    }

    public function purge()
    {
        $ini = $this->meta->getIniFile();
        unlink( $ini );
        unlink( $ini . '.disabled' );
    }

    public function buildMetaFromName($name)
    {
        $path = Config::getBuildDir() . '/' . Config::getCurrentPhpName() . '/ext/'. $name;
        $xml = $path . '/package.xml';
        $m4 = $path . '/config.m4';

        if(file_exists($xml)) {
            $this->logger->warning("===> usin xml meta");
            $meta = new ExtensionMetaXml($xml);
        }
        elseif(file_exists($m4)) {
            $this->logger->warning("===> usin m4 meta");
            $meta = new ExtensionMetaM4($m4);
        }
        else {
            $this->logger->warning("===> usin polyfill meta");
            $meta = new ExtensionMetaPolyfill($name);
        }

        return $meta;
    }

}
