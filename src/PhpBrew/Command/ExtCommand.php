<?php
namespace PhpBrew\Command;

use PhpBrew\Config;
use PhpBrew\Utils;
use PhpBrew\Extension\ExtensionFactory;
use CLIFramework\Command;
use GetOptionKit\OptionCollection;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FilesystemIterator;

class ExtCommand extends Command
{

    public function usage()
    {
        return 'phpbrew ext [install|enable|disable|config]';
    }

    public function brief()
    {
        return 'List extensions or execute extension subcommands';
    }

    public function init()
    {
        $this->command('enable');
        $this->command('install');
        $this->command('disable');
        $this->command('config');
        $this->command('clean');
    }

    /**
     * @param GetOptionKit\OptionCollection $opts
     */
    public function options($opts)
    {
        $opts->add('v|php:', 'The php version for which we install the module.');
    }

    public function execute()
    {
        $buildDir = Config::getCurrentBuildDir();
        $extDir = $buildDir . DIRECTORY_SEPARATOR . 'ext';
        $loaded = array_map('strtolower', get_loaded_extensions());

        // list for extensions which are not enabled
        $extensions = array();


        if (file_exists($extDir) && is_dir($extDir)) {
            $this->logger->debug("Scanning $extDir...");
            foreach( scandir($extDir) as $extName) {
                if ($extName == "." || $extName == "..") {
                    continue;
                }
                if (ExtensionFactory::configM4Exists($extDir . DIRECTORY_SEPARATOR . $extName)) {
                    if (in_array($extName, $loaded)) {
                        continue;
                    }
                    $extensions[] = $extName;
                }
            }
        }

        $this->logger->info('Loaded extensions:');
        foreach ($loaded as $ext) {
            $this->logger->info("  [*] $ext");
        }

        $this->logger->info('Available local extensions:');

        foreach ($extensions as $ext) {
            $this->logger->info("  [ ] $ext");
        }
    }
}
