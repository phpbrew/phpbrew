<?php
namespace PhpBrew\Command;
use PhpBrew\Config;

class SwitchCommand extends \CLIFramework\Command
{
    public function brief() { return 'switch php version'; }

    public function execute($version = null)
    {
    }
}
