<?php
namespace PhpBrew\Command;
use Exception;

class VirtualCommand extends \CLIFramework\Command
{
    public function execute($version = null)
    {
        throw new Exception("You should not see this, please check if bashrc is sourced in your shell.");
    }
}




