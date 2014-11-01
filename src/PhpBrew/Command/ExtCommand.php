<?php
namespace PhpBrew\Command;

use PhpBrew\Config;
use PhpBrew\Utils;
use PhpBrew\Extension\ExtensionFactory;
use PhpBrew\Extension\PeclExtension;
use PhpBrew\Extension\M4Extension;
use PhpBrew\Extension\Extension;
use CLIFramework\Command;
use GetOptionKit\OptionCollection;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Exception;

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

    public function describeExtension(Extension $ext) 
    {
        if (extension_loaded($ext->getExtensionName())) {
            $this->logger->info(' [*] ' . $ext->getExtensionName());
        } else {
            $this->logger->info(' [ ] ' . $ext->getExtensionName());
        }

        $padding = '     ';
        if ($ext instanceof M4Extension) {
            $this->logger->info($padding . 'Configure file: ' . $ext->getConfigM4Path());

            $options = $ext->getConfigureOptions();
            if (!empty($options)) {
                $this->logger->info($padding . 'Configure options:');
                foreach($options as $option) {
                    $this->logger->info($padding . '  ' 
                        . sprintf('%-32s %s', 
                            $option->option . ($option->valueHint ? '[=' . $option->valueHint . ']' : ''),
                            $option->desc ));
                }
            }
        }
    }

    public function execute()
    {
        $buildDir = Config::getCurrentBuildDir();
        $extDir = $buildDir . DIRECTORY_SEPARATOR . 'ext';

        // list for extensions which are not enabled
        $extensions = array();
        $extensionNames = array();

        if (file_exists($extDir) && is_dir($extDir)) {
            $this->logger->debug("Scanning $extDir...");
            foreach( scandir($extDir) as $extName) {
                if ($extName == "." || $extName == "..") {
                    continue;
                }
                $dir = $extDir . DIRECTORY_SEPARATOR . $extName;
                if ($m4files = ExtensionFactory::configM4Exists($dir)) {
                    $this->logger->debug("Loading extension information $extName from $dir");

                    foreach ($m4files as $m4file) {
                        try {
                            $ext = ExtensionFactory::createM4Extension($extName, $m4file);
                            // $ext = ExtensionFactory::createFromDirectory($extName, $dir);
                            $extensions[$ext->getName()] = $ext;
                            $extensionNames[] = $extName;
                            break;
                        } catch(Exception $e) {

                        }
                    }
                }
            }
        }

        $this->logger->info('Loaded extensions:');
        foreach ($extensions as $extName => $ext) {
            if (extension_loaded($extName)) {
                $this->describeExtension($ext);
            }
        }

        $this->logger->info('Available local extensions:');
        foreach ($extensions as $extName => $ext) {
            if (extension_loaded($extName)) {
                continue;
            }
            $this->describeExtension($ext);
        }
    }
}
