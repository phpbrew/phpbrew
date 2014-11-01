<?php
namespace PhpBrew\Command\ExtensionCommand;
use PhpBrew\Config;
use PhpBrew\Extension;
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
        var_dump( $ext );
    }
}


