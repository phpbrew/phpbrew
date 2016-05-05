<?php
namespace PhpBrew\Extension;

use Exception;
use CLIFramework\Logger;
use PhpBrew\Extension\Extension;
use PhpBrew\Extension\ExtensionInstaller;
use PhpBrew\Utils;
use PhpBrew\Config;
use PhpBrew\Tasks\MakeTask;

class ExtensionManager
{
    public $logger;

    /**
     * Map of extensions that can't be enabled at the same time.
     * This helps phpbrew to unload antagonist extensions before enabling
     * an extension with a known conflict.
     * @var array
     */
    protected $conflicts = array(
        'json'  => array('jsonc'),   // enabling jsonc disables json
        'jsonc' => array('json'),    // enabling json disables jsonc
    );

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function purgeExtension(Extension $ext)
    {
        if ($sourceDir = $ext->getSourceDirectory()) {
            $currentPhpExtensionDirectory = Config::getBuildDir() . '/' . Config::getCurrentPhpName() . '/ext';
            $extName = $ext->getExtensionName();
            $extensionDir = $currentPhpExtensionDirectory . DIRECTORY_SEPARATOR . $extName;
            if (file_exists($extensionDir)) {
                Utils::system("rm -rvf $extensionDir");
            }
        }
    }


    public function cleanExtension(Extension $ext)
    {
        $make = new MakeTask($this->logger);
        $make->setQuiet();
        $code = ! is_dir($sourceDir = $ext->getSourceDirectory()) ||
                ! $ext->isBuildable() ||
                ! $make->clean($ext);

        if ($code != 0) {
            $this->logger->error("Could not clean extension: {$ext->getName()}.");
        }

        return $code == 0;
    }

    /**
     * Whenever you call this method, you shall have already downloaded the extension
     * And have set the source directory on the Extension object.
     */
    public function installExtension(Extension $ext, array $options = array())
    {
        $this->disableExtension($ext);

        $sourceDir = $ext->getSourceDirectory();
        $name = $ext->getName();

        if (!file_exists($sourceDir)) {
            throw new Exception("Source directory $sourceDir does not exist.");
        }

        // Install local extension
        $installer = new ExtensionInstaller($this->logger);
        $this->logger->info("===> Installing {$name} extension...");
        $this->logger->debug("Extension path $sourceDir");
        // $installer->runInstall($name, $sourceDir, $options);
        $installer->install($ext, $options);

        $this->createExtensionConfig($ext);
        $this->enableExtension($ext);
        $this->logger->info("Done.");
        return $sourceDir;
    }

    public function createExtensionConfig(Extension $ext)
    {
        $sourceDir = $ext->getSourceDirectory();
        $ini = $ext->getConfigFilePath() . '.disabled';
        $this->logger->info("===> Creating config file {$ini}");

        if (!file_exists(dirname($ini))) {
            mkdir(dirname($ini), 0755, true);
        }

        // create extension config file
        if (file_exists($ini)) {
            return;
        }
        if ($ext->isZend()) {
            $makefile = file_get_contents("$sourceDir/Makefile");
            preg_match('/EXTENSION\_DIR\s=\s(.*)/', $makefile, $regs);
            $content = "zend_extension=" . $ext->getSharedLibraryPath();
        } else {
            $content = "extension=" . $ext->getSharedLibraryName();
        }
        file_put_contents($ini, $content);
        $this->logger->debug("{$ini} is created.");
    }


    public function disable($extensionName)
    {
        $ext = ExtensionFactory::lookup($extensionName);
        if (!$ext) {
            $ext = ExtensionFactory::lookupRecursive($extensionName);
        }
        if ($ext) {
            return $this->disableExtension($ext);
        } else {
            $this->logger->info("{$extensionName} extension is not installed. ");
        }
    }

    public function enable($extensionName)
    {
        $ext = ExtensionFactory::lookup($extensionName);
        if (!$ext) {
            $ext = ExtensionFactory::lookupRecursive($extensionName);
        }
        if ($ext) {
            return $this->enableExtension($ext);
        } else {
            $this->logger->info("{$extensionName} extension is not installed. ");
        }
    }


    /**
     * Enables ini file for current extension
     * @return boolean
     */
    public function enableExtension(Extension $ext)
    {
        $name = $ext->getExtensionName();
        $this->logger->info("===> Enabling extension $name");
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
        $name = $ext->getExtensionName();
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
            $conflicts = $this->conflicts[$name];
            $this->logger->info("===> Applying conflicts resolution (" . implode(', ', $conflicts) . "):");
            foreach ($conflicts as $extensionName) {
                $ext = ExtensionFactory::lookup($extensionName);
                $this->disableExtension($ext);
            }
        }
    }

    public function hasConflicts(Extension $ext)
    {
        return array_key_exists($ext->getName(), $this->conflicts);
    }
}
