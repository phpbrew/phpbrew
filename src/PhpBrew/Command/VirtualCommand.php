<?php

namespace PhpBrew\Command;

/*
 * @codeCoverageIgnore
 */
use Exception;

class VirtualCommand extends \CLIFramework\Command
{
    public function execute($version = null)
    {
        throw new Exception("You should not see this, if you see this, it means you didn't load the ~/.phpbrew/bashrc script, please check if bashrc is sourced in your shell.");
    }
}
