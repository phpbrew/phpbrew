<?php
namespace PhpBrew\Command;
use Exception;

class RemoveCommand extends \CLIFramework\Command
{
    public function brief() { return 'remove installed php version.'; }

    public function execute($version = null)
    {
        throw new Exception("You should not see this, please check if phpbrew bashrc is sourced in your shell.");
    }
}
