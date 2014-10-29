<?php
namespace PhpBrew\Extension;
use CLIFramework\Logger;
use PhpBrew\ExtensionInstaller;
// use PhpBrew\Extension;
use PhpBrew\Extension\Extension;
use PhpBrew\ExtensionMetaXml;

class ExtensionManager
{
    public $logger;

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

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function installExtension(Extension $ext, $version = 'stable', array $options = array(), $pecl = false)
    {
        $originalLevel = $this->logger->getLevel();
        $this->logger->quiet();
        $this->disableExtension($ext);
        $this->logger->setLevel($originalLevel);

        $path = $ext->getSourceDirectory();
        $name = $ext->getName();

        // Install local extension
        if (file_exists($path) && ! $pecl) {
            $installer = new ExtensionInstaller($this->logger);
            $this->logger->info("===> Installing {$name} extension...");
            $this->logger->debug("Extension path $path");
            $installer->runInstall($name, $path, $options);
        } else {
            $extDir = dirname($path);
            if (!file_exists($extDir)) {
                mkdir($extDir, 0755, true);
            }
            chdir($extDir);
            $peclInstaller = new PeclExtensionInstaller($this->logger);
            $peclInstaller->install($name, $version, $options);
            // $xml = $installer->installFromPecl($name, $version, $options);
        }

        $ini = $ext->getConfigFilePath() . '.disabled';
        $this->logger->info("===> Creating config file {$ini}");

        // create extension config file
        if (! file_exists($ini)) {
            if ($ext->isZend()) {
                $makefile = file_get_contents("$path/Makefile");
                preg_match('/EXTENSION\_DIR\s=\s(.*)/', $makefile, $regs);

                $content = "zend_extension={$regs[1]}/";
            } else {
                $content = "extension=";
            }

            file_put_contents($ini, $content .= $ext->getSharedLibraryName()); // {ext name}.so
            $this->logger->debug("{$ini} is created.");
        }

        $this->logger->info("===> Enabling extension...");
        $this->enableExtension($ext);
        $this->logger->info("Done.");
        return $path;
    }


    public function disable($extensionName) {
        $ext = ExtensionFactory::lookup($extensionName);
        return $this->disableExtension($ext);
    }
    
    public function enable($extensionName) {
        $ext = ExtensionFactory::lookup($extensionName);
        return $this->enableExtension($ext);
    }


    /**
     * Enables ini file for current extension
     * @return boolean
     */
    public function enableExtension(Extension $ext)
    {
        $name = $ext->getName();
        $enabled_file = $ext->getConfigFilePath();
        $disabled_file = $enabled_file . '.disabled';
        if (file_exists($enabled_file) && ($ext->isLoaded() && ! $this->hasConflicts($ext))) {
            $this->logger->info("[*] {$name} extension is already enabled.");
            return true;
        }

        if (file_exists($disabled_file)) {
            $this->disableAntagonists($ext);

            if (rename($disabled_file, $enabled_file)) {
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
    public function disableExtension(Extension $ext)
    {
        $name = $ext->getName();
        $enabled_file = $ext->getConfigFilePath();
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
    public function disableAntagonists(Extension $ext)
    {
        $name = $ext->getName();
        if (isset($this->conflicts[$name])) {
            $conflicts = $ext->conflicts[$name];
            $this->logger->info("===> Applying conflicts resolution (" . implode(', ', $conflicts) . "):");
            foreach ($conflicts as $extensionName) {
                $ext = ExtensionFactory::lookup($extensionName);
                $this->disable($ext);
            }
        }
    }

    public function hasConflicts(Extension $ext)
    {
        return array_key_exists($ext->getName(), $this->conflicts);
    }


}



