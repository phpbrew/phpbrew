<?php

namespace PhpBrew\Extension;

use RuntimeException;
use CLIFramework\Logger;
use Exception;
use PhpBrew\Config;
use PhpBrew\Tasks\MakeTask;
use PhpBrew\Utils;

class ExtensionManager
{
    public $logger;

    /**
     * Map of extensions that can't be enabled at the same time.
     * This helps phpbrew to unload antagonist extensions before enabling
     * an extension with a known conflict.
     *
     * @var array
     */
    protected $conflicts = array(
        'json' => array('jsonc'),   // enabling jsonc disables json
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
        $code = !is_dir($sourceDir = $ext->getSourceDirectory()) ||
                !$ext->isBuildable() ||
                !$make->clean($ext);

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
        $this->logger->info('Done.');

        return $sourceDir;
    }

    public function createExtensionConfig(Extension $ext)
    {
        $ini = $ext->getConfigFilePath();
        $this->logger->info("===> Creating config file {$ini}");

        if (!file_exists(dirname($ini))) {
            if (!mkdir($concurrentDirectory = dirname($ini), 0755, true) && !is_dir($concurrentDirectory)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
            }
        }

        if (file_exists($ini)) {
            return true;
        }

        // @see https://github.com/php/php-src/commit/0def1ca59a
        $content = sprintf(
            '%s=%s' . PHP_EOL,
            $ext->isZend() ? 'zend_extension' : 'extension',
            $ext->isZend() && PHP_VERSION_ID < 50500
                ? $ext->getSharedLibraryPath()
                : $ext->getSharedLibraryName()
        );

        // create extension config file
        if (file_put_contents($ini, $content) === false) {
            return false;
        }

        $this->logger->debug("{$ini} is created.");

        return true;
    }

    public function disable($extensionName, $sapi = null)
    {
        $ext = ExtensionFactory::lookup($extensionName);
        if (!$ext) {
            $ext = ExtensionFactory::lookupRecursive($extensionName);
        }
        if ($ext) {
            return $this->disableExtension($ext, $sapi);
        } else {
            $this->logger->info("{$extensionName} extension is not installed. ");
        }
    }

    public function enable($extensionName, $sapi = null)
    {
        $ext = ExtensionFactory::lookup($extensionName);
        if (!$ext) {
            $ext = ExtensionFactory::lookupRecursive($extensionName);
        }
        if ($ext) {
            return $this->enableExtension($ext, $sapi);
        } else {
            $this->logger->info("{$extensionName} extension is not installed. ");
        }
    }

    /**
     * Enables ini file for current extension.
     *
     * @return bool
     */
    public function enableExtension(Extension $ext, $sapi = null)
    {
        $name = $ext->getExtensionName();
        $this->logger->info("===> Enabling extension $name");

        if ($sapi) {
            return $this->enableSapiExtension($ext, $name, $sapi, true);
        }

        $first = true;
        $result = true;
        foreach (Config::getSapis() as $availableSapi) {
            $result = $result && $this->enableSapiExtension($ext, $name, $availableSapi, $first);
            $first = false;
        }

        return $result;
    }

    private function enableSapiExtension(Extension $ext, $name, $sapi, $first = false)
    {
        $default_file = $ext->getConfigFilePath();
        $enabled_file = $ext->getConfigFilePath($sapi);
        if (file_exists($enabled_file) && ($ext->isLoaded() && !$this->hasConflicts($ext))) {
            $this->logger->info("[*] {$name} extension is already enabled for SAPI {$sapi}.");

            return true;
        }

        if (
            $first
            && !file_exists($default_file)
            && !(file_exists($ext->getSharedLibraryPath())
            && $this->createExtensionConfig($ext))
        ) {
            $this->logger->info("{$name} extension is not installed. Suggestions:");
            $this->logger->info("\t\$ phpbrew ext install {$name}");

            return false;
        }

        if (!file_exists(dirname($enabled_file))) {
            return true;
        }

        $this->disableAntagonists($ext, $sapi);

        $disabled_file = $enabled_file . '.disabled';
        if (file_exists($disabled_file)) {
            if (!rename($disabled_file, $enabled_file)) {
                $this->logger->warning("failed to re-enable {$name} extension for SAPI {$sapi}.");

                return false;
            }

            $this->logger->info("[*] {$name} extension is re-enabled for SAPI {$sapi}.");
            return true;
        }

        if (!copy($default_file, $enabled_file)) {
            $this->logger->warning("failed to enable {$name} extension for SAPI {$sapi}.");

            return false;
        }

        $this->logger->info("[*] {$name} extension is enabled for SAPI {$sapi}.");

        return true;
    }

    /**
     * Disables ini file for current extension.
     *
     * @return bool
     */
    public function disableExtension(Extension $ext, $sapi = null)
    {
        $name = $ext->getExtensionName();

        if (null !== $sapi) {
            return $this->disableSapiExtension($ext->getConfigFilePath($sapi), $name, $sapi);
        }

        $result = true;
        foreach (Config::getSapis() as $availableSapi) {
            $result = $result && $this->disableSapiExtension($ext->getConfigFilePath($availableSapi), $name, $sapi);
        }

        return $result;
    }

    private function disableSapiExtension($extension_file, $name, $sapi)
    {
        if (!file_exists(dirname($extension_file))) {
            return true;
        }

        if (!file_exists($extension_file)) {
            $this->logger->info("[ ] {$name} extension is already disabled for SAPI {$sapi}.");

            return true;
        }

        if (file_exists($extension_file)) {
            if (rename($extension_file, $extension_file . '.disabled')) {
                $this->logger->info("[ ] {$name} extension is disabled for SAPI {$sapi}.");

                return true;
            }
            $this->logger->warning("failed to disable {$name} extension for SAPI {$sapi}.");
        }

        return false;
    }

    /**
     * Disable extensions known to conflict with current one.
     */
    public function disableAntagonists(Extension $ext, $sapi = null)
    {
        $name = $ext->getName();
        if (isset($this->conflicts[$name])) {
            $conflicts = $this->conflicts[$name];
            $this->logger->info('===> Applying conflicts resolution (' . implode(', ', $conflicts) . '):');
            foreach ($conflicts as $extensionName) {
                $ext = ExtensionFactory::lookup($extensionName);
                $this->disableExtension($ext, $sapi);
            }
        }
    }

    public function hasConflicts(Extension $ext)
    {
        return array_key_exists($ext->getName(), $this->conflicts);
    }
}
