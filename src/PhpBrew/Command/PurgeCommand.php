<?php
namespace PhpBrew\Command;
/**
 * @codeCoverageIgnore
 */

use PhpBrew\Config;
use Exception;

class PurgeCommand extends \CLIFramework\Command
{

    public function arguments($args) {
        $args->add('installed php')
            ->validValues(\PhpBrew\Config::getInstalledPhpVersions())
            ;
    }

    public function brief()
    {
        return 'Remove installed php version and config files.';
    }

    public function execute($version = null)
    {
        throw new Exception("You should not see this, please check if phpbrew bashrc is sourced in your shell.");
    }
}
