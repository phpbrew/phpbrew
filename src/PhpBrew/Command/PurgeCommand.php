<?php

namespace PhpBrew\Command;

use CLIFramework\Command;
use PhpBrew\Config;
use Exception;

/**
 * @codeCoverageIgnore
 */
class PurgeCommand extends Command
{
    public function arguments($args)
    {
        $args->add('installed php')
            ->validValues(function () {
                return Config::getInstalledPhpVersions();
            })
            ;
    }

    public function brief()
    {
        return 'Remove installed php version and config files.';
    }

    public function execute($version = null)
    {
        throw new Exception('You should not see this, please check if phpbrew bashrc is sourced in your shell.');
    }
}
