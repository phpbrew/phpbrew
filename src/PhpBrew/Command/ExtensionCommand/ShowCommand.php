<?php
namespace PhpBrew\Command\ExtensionCommand;

use PhpBrew\Config;
use PhpBrew\Extension\Extension;
use PhpBrew\Extension\PeclExtension;
use PhpBrew\Extension\ExtensionManager;
use PhpBrew\Extension\ExtensionFactory;
use Exception;
use PhpBrew\Command\ExtensionCommand\BaseCommand;

class ShowCommand extends BaseCommand
{
    public function usage()
    {
        return 'phpbrew [-dv, -r] ext show [extension name]';
    }

    public function brief()
    {
        return 'Show information of a PHP extension';
    }

    public function options($opts) {
        $opts->add('download', 'download the extensino source if extension not found.');
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
            'Source Directory' => $ext->getSourceDirectory(),
            'Config' => $ext->getConfigM4Path(),
            'INI File' => $ext->getConfigFilePath(),
            'Extension'    => ($ext instanceof PeclExtension) ? 'Pecl' : 'Core',
            'Zend'              => $ext->isZend() ? 'yes' : 'no',
            'Loaded' => (extension_loaded($ext->getExtensionName()) 
                ? $this->formatter->format('yes','green')
                : $this->formatter->format('no', 'red')),
        );

        foreach($info as $label => $val) {
            $this->logger->writeln(sprintf('%20s: %s', $label, $val));
        }

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

    public function execute($extensionName)
    {
        $ext = ExtensionFactory::lookup($extensionName);

        if (!$ext) {
            $ext = ExtensionFactory::lookupRecursive($extensionName);
        }

        // Extension not found, use pecl to download it.
        if (!$ext && $this->options->{'download'}) {

            $extensionList = new ExtensionList;
            // initial local list
            $extensionList->initLocalExtensionList($this->logger, $this->options);

            $hosting = $extensionList->exists($extensionName);

            $downloader = new ExtensionDownloader($this->logger, $this->options);
            $extDir = $downloader->download($hosting, 'latest');
            // Reload the extension
            $ext = ExtensionFactory::lookupRecursive($extensionName, array($extDir));
        }
        if (!$ext) {
            throw new Exception("$extensionName extension not found.");
        }
        $this->describeExtension($ext);
    }
}


