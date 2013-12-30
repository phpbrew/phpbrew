<?php

namespace PhpBrew;

use PhpBrew\Config;
use PhpBrew\ExtensionInstaller;
use PhpBrew\ExtensionInterface;

class Extension implements ExtensionInterface
{
    protected $name;
    protected $logger;
    protected $config;

    /**
     * List of zend extensions
     * @var array
     */
    protected $zend = array (
        'opcache',
        'xdebug',
        'xhprof'
    );

    /**
     * Maps extensions that have binary file name different from extension name
     * This helps phpbrew to source correct {EXTENSION}.so file
     * @var array
     */
    protected $sources = array (
        'jsonc' 	=> 'json',		// jsonc loads json.so
        'markdown' 	=> 'discount',	// markdown loads discount.so
        'pecl_http' => 'http',		// pecl_http loads http.so
    );

    /**
     * Map of extensions that can't be enabled at the same time.
     * This helps phpbrew to unload antagonist extensions before enabling
     * an extension with a known conflict.
     * @var array
     */
    protected $conflicts = array (
        'json' 	=> ['jsonc'],	// enabling jsonc disables json
        'jsonc' => ['json'],	// enabling json disables jsonc
    );

    public function __construct($name, $logger)
    {
        $this->name = strtolower($name);
        $this->logger = $logger;
        $this->config = $this->solveConfigFileName();
    }

    public function install($version = 'stable', array $options = array())
    {
        $this->logger->quiet();
        $this->disable();
        $this->logger->setLevel(4);

        $php = Config::getCurrentPhpName();
        $buildDir = Config::getBuildDir();
        $extDir = $buildDir . DIRECTORY_SEPARATOR . $php . DIRECTORY_SEPARATOR . 'ext';
        $installer = new ExtensionInstaller($this->logger);
        $path = $extDir . DIRECTORY_SEPARATOR . $this->name;

        // Install local extension
        if ( file_exists( $path ) ) {
            $this->logger->info("===> Installing {$this->name} extension...");
            $this->logger->debug("Extension path $path");
            $extension_so = $installer->runInstall($this->name, $path, $options);
        } else {
            chdir($extDir);
            $extension_so = $installer->installFromPecl($this->name, $version ,$options);
        }

        $this->logger->info("===> Creating config file {$this->config}");
        $config_file = $this->config . '.disabled';
        // create extension config file
        if ( file_exists($config_file) ) {
            $lines = file($config_file);
            foreach ($lines as &$line) {
                if ( preg_match('#^;\s*((?:zend_)?extension\s*=.*)#', $line, $regs ) ) {
                    $line = $regs[1];
                }
            }
            file_put_contents($config_file, join('', $lines) );
        } else {
            if ( $this->isZend() ) {
                $content = "zend_extension={$extension_so}";
            } else {
                $extension_so = $this->solveSourceFileName();
                $content = "extension={$extension_so}";
            }
            file_put_contents($config_file,$content);
            $this->logger->debug("{$this->config} is created.");
        }
        $this->logger->info("===> Enabling extension...");
        $this->enable();
        $this->logger->info("Done.");

        return $this->config;
    }

    /**
     * Enables ini file for current extension
     * @return boolean
     */
    public function enable()
    {
        $disabled_file = $this->config . '.disabled';
        if (file_exists($this->config)) {
            $this->logger->info("[*] {$this->name} extension is already enabled.");

            return true;
        }

        if (file_exists($disabled_file)) {
            $this->disableAntagonists();
            if ( rename($disabled_file, $this->config) ) {
                $this->logger->info("[*] {$this->name} extension is enabled.");

                return true;
            }
            $this->logger->warning("failed to enable {$this->name} extension.");
        }
        $this->logger->info("{$this->name} extension is not installed. Suggestions:");
        $this->logger->info("\t\$ phpbrew ext install {$this->name}");

        return false;
    }

    /**
     * Disables ini file for current extension
     * @return boolean
     */
    public function disable()
    {
        $disabled_file = $this->config . '.disabled';

        if (file_exists($disabled_file)) {
            $this->logger->info("[ ] {$this->name} extension is already disabled.");

            return true;
        }

        if (file_exists($this->config)) {
            if (rename($this->config, $disabled_file)) {
                $this->logger->info("[ ] {$this->name} extension is disabled.");

                return true;
            }
            $this->logger->warning("failed to disable {$this->name} extension.");
        }

        return false;
    }

    /**
     * Disable extensions known to conflict with current one
     */
    public function disableAntagonists()
    {
        if (isset($this->conflicts[$this->name])) {
            $conflicts = $this->conflicts[$this->name];
            $this->logger->info("===> Applying conflicts resolution (" . implode(', ', $conflicts) . "):");
            foreach ($conflicts as $extension) {
                (new Extension($extension, $this->logger))->disable();
            }
        }
    }

    /**
     * Checks if current extension is a zend engine extension
     * @return boolean
     */
    final public function isZend()
    {
        if(in_array($this->name, $this->zend)) return true;

        return false;
    }

    /**
     * Checks if current extension is loaded
     * @return boolean
     */
    public function isLoaded()
    {
        return extension_loaded($this->name);
    }

    /**
     * Checks if current extension is available for local install
     * @return boolean
     */
    public function isAvailable()
    {
        $extDir = Config::getBuildDir() . '/' . Config::getCurrentPhpName() . '/' . 'ext';
        foreach (glob($extDir.'/*', GLOB_ONLYDIR) as $available_extension) {
            if (false !== strpos(basename($available_extension), $this->name)) {
                return true;
            }
        }

        return false;
    }

    public function purge()
    {
        unlink( $this->config );
        unlink( $this->config . '.disabled' );
    }

    final public function solveConfigFileName()
    {
        $path = Config::getCurrentPhpConfigScanPath() . DIRECTORY_SEPARATOR . $this->name . '.ini';
        if ( ! file_exists( dirname($path) ) ) {
            mkdir(dirname($path),0755,true);
        }

        return $path;
    }

    final public function solveSourceFileName()
    {
        return (isset($this->sources[$this->name]) ? $this->sources[$this->name] : $this->name) . '.so';
    }
}
