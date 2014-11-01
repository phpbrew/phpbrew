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

class ExtensionCommand extends Command
{
    public function aliases() {
        return array('ext');
    }

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
        $this->command('show');
    }

    /**
     * @param GetOptionKit\OptionCollection $opts
     */
    public function options($opts)
    {
        $opts->add('so|show-options', 'Show extension configure options');
        $opts->add('sp|show-path', 'Show extension config.m4 path');
    }

    public function describeExtension(Extension $ext) 
    {
        $this->logger->write(sprintf(' [%s] %-16s',
            extension_loaded($ext->getExtensionName()) ? '*' : ' ' ,
            $ext->getExtensionName(),
            $ext->getConfigM4Path())
        );

        if ($this->options->{'show-path'}) {
            $this->logger->write(sprintf(' from %s', $ext->getConfigM4Path()));
        }
        $this->logger->newline();

        // $this->logger->writeln(sprintf('config: %s', $ext->getConfigFilePath()));

        if ($this->options->{'show-options'}) {
            $padding = '     ';
            if ($ext instanceof M4Extension) {
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
