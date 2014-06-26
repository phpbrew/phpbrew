<?php
namespace PhpBrew\Command;

use CLIFramework\Command;
use Exception;

class PurgeCommand extends Command
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
