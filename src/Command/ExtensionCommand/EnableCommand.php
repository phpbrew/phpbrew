<?php

namespace PHPBrew\Command\ExtensionCommand;

use PHPBrew\Config;
use PHPBrew\Extension\ExtensionManager;

class EnableCommand extends BaseCommand
{
    public function usage()
    {
        return 'phpbrew ext enable [extension name]';
    }

    public function brief()
    {
        return 'Enable PHP extension';
    }

    public function arguments($args)
    {
        $args->add('extensions')
            ->suggestions(function () {
                $extension = '.ini.disabled';

                return array_map(function ($path) use ($extension) {
                    return basename($path, $extension);
                }, glob(Config::getCurrentPhpDir() . "/var/db/*{$extension}"));
            });
    }

    public function execute($extensionName)
    {
        $manager = new ExtensionManager($this->logger);
        $manager->enable($extensionName);
    }
}
