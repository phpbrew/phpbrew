<?php

namespace PHPBrew\Command\ExtensionCommand;

use PHPBrew\Config;
use PHPBrew\Extension\ExtensionFactory;
use PHPBrew\Extension\ExtensionManager;

class CleanCommand extends BaseCommand
{
    public function brief()
    {
        return 'Clean up the compiled objects in the extension source directory.';
    }

    public function options($opts)
    {
        $opts->add('p|purge', 'Remove all the source files.');
    }

    public function arguments($args)
    {
        $args->add('extensions')
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
        if ($ext = ExtensionFactory::lookup($extensionName)) {
            $this->logger->info("Cleaning $extensionName...");
            $manager = new ExtensionManager($this->logger);

            if ($this->options->purge) {
                $manager->purgeExtension($ext);
            } else {
                $manager->cleanExtension($ext);
            }
            $this->logger->info('Done');
        }
    }
}
