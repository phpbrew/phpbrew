<?php
namespace PhpBrew\Command;

use PhpBrew\Config;
use Exception;

class PurgeCommand extends \CLIFramework\Command
{
    public function brief()
    {
        return 'remove installed php version and config files.';
    }

    public function execute($version = null)
    {
        throw new Exception("You should not see this, please check if phpbrew bashrc is sourced in your shell.");
    }
}
