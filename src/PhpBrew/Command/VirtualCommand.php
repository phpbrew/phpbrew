<?php
namespace PhpBrew\Command;

use CLIFramework\Command;
use Exception;

class VirtualCommand extends Command
{
    public function execute($version = null)
    {
        throw new Exception("You should not see this, please check if bashrc is sourced in your shell.");
    }
}
