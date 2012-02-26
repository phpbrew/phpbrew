<?php
namespace PhpBrew\Command;
use PhpBrew\Config;

class UseCommand extends \CLIFramework\Command
{
    public function brief() { return 'use php, switch version temporarily'; }

    public function execute($version = null)
    {
    }
}
