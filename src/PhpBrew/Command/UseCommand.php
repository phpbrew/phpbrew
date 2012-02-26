<?php
namespace PhpBrew\Command;
use PhpBrew\Config;
use Exception;

class UseCommand extends \CLIFramework\Command
{
    public function brief() { return 'use php, switch version temporarily'; }

    public function execute($version = null)
    {
        throw new Exception("You should not see this, please check if bashrc is sourced in your shell.");
    }
}
