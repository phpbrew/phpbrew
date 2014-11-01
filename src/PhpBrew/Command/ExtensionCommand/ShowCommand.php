<?php
namespace PhpBrew\Command\ExtensionCommand;
use PhpBrew\Config;
use PhpBrew\Extension\Extension;
use PhpBrew\Extension\M4Extension;
use PhpBrew\Extension\ExtensionManager;
use PhpBrew\Extension\ExtensionFactory;
use PhpBrew\Extension\PeclExtensionInstaller;
use PhpBrew\Extension\PeclExtensionDownloader;
use PhpBrew\Utils;

class ShowCommand extends \CLIFramework\Command
{
    public function usage()
    {
        return 'phpbrew [-dv, -r] ext show [extension name]';
    }

    public function brief()
    {
        return 'Show information of a PHP extension';
    }

    public function arguments($args)
    {
        $args->add('extension')
            ->suggestions(function () {
                $extdir = Config::getBuildDir() . '/' . Config::getCurrentPhpName() . '/ext';
                return array_filter(
                    scandir($extdir),
                    function ($d) use ($extdir) {
                        return $d != '.' && $d != '..' && is_dir($extdir . DIRECTORY_SEPARATOR . $d);
                    }
                );
            });
    }

    public function describeExtension(Extension $ext) 
    {

        $info = array(
            'Name' => $ext->getExtensionName(),
            'Config' => $ext->getConfigM4Path(),
            'INI File' => $ext->getConfigFilePath(),
            'Extension Type'    => ($ext instanceof PeclExtension) ? 'Pecl extension' : 'Core extension',
            'Zend'              => $ext->isZend() ? 'yes' : 'no',
            'Loaded' => (extension_loaded($ext->getExtensionName()) ? 'yes' : 'no'),
        );

        foreach($info as $label => $val) {
            $this->logger->writeln(sprintf('%20s: %s', $label, $val));
        }

        if ($ext instanceof M4Extension) {
            $options = $ext->getConfigureOptions();
            if (!empty($options)) {
                $this->logger->newline();
                $this->logger->writeln(sprintf('%20s: ', 'Configure Options'));
                $this->logger->newline();
                foreach($options as $option) {
                    $this->logger->writeln(sprintf('        %-32s %s', 
                            $option->option . ($option->valueHint ? '[=' . $option->valueHint . ']' : ''),
                            $option->desc));
                    $this->logger->newline();
                }
            }
        }
    }


    public function execute($extensionName)
    {
        $manager = new ExtensionManager($this->logger);
        $ext = ExtensionFactory::lookup($extensionName);

        // Extension not found, use pecl to download it.
        if (!$ext) {
            /*
            $peclDownloader = new PeclExtensionDownloader($this->logger);
            $peclDownloader->download($extensionName, $extConfig->version);
            // Reload the extension
            $ext = ExtensionFactory::lookup($extensionName);
            */
        }
        if (!$ext) {
            throw new Exception("$extensionName not found.");
        }
        $this->describeExtension($ext);
    }
}


