<?php

namespace PhpBrew\Command\ExtensionCommand;

use PhpBrew\Config;
use PhpBrew\Extension\ExtensionManager;

class DisableCommand extends BaseCommand
{
    public function usage()
    {
        return 'phpbrew ext disable [extension name]';
    }

    public function brief()
    {
        return 'Disable PHP extension';
    }

    public function arguments($args)
    {
        $args->add('extensions')
            ->suggestions(function () {
                $extension = '.ini';

                return array_map(function ($path) use ($extension) {
                    return basename($path, $extension);
                }, glob(Config::getCurrentPhpDir() . "/var/db/*{$extension}"));
            });
    }

    public function execute($extensionName)
    {
        $manager = new ExtensionManager($this->logger);
        $manager->disable($extensionName);
    }
}
