<?php

namespace PhpBrew\Command;

use CLIFramework\Command;
use Exception;
use PhpBrew\BuildFinder;
use PhpBrew\Config;

/**
 * @codeCoverageIgnore
 */
class PurgeCommand extends VirtualCommand
{
    public function arguments($args)
    {
        $args->add('PHP build')
            ->validValues(function () {
                return BuildFinder::findInstalledBuilds();
            })
            ->multiple()
            ;
    }

    public function brief()
    {
        return 'Remove installed php version and config files.';
    }
}
