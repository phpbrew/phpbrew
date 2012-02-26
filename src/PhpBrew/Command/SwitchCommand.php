<?php
namespace PhpBrew\Command;
use PhpBrew\Config;
use Exception;

class SwitchCommand extends \CLIFramework\Command
{
    public function brief() { return 'switch php version as default.'; }

    public function execute($version = null)
    {
        throw new Exception("You should not see this, please check if bashrc is sourced in your shell.");
    }
}
