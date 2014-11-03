<?php
namespace PhpBrew\Command;

/**
 * @codeCoverageIgnore
 */

use Exception;

class RemoveCommand extends \CLIFramework\Command
{
    public function brief()
    {
        return 'Remove installed php version.';
    }

    public function arguments($args) {
        $args->add('installed php')
            ->validValues('PhpBrew\\Config::getInstalledPhpVersions')
            ;
    }

    public function execute($version = null)
    {
        throw new Exception("You should not see this, please check if phpbrew bashrc is sourced in your shell.");
    }
}
