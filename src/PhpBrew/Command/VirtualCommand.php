<?php

namespace PhpBrew\Command;

use Exception;
use CLIFramework\Command;

/**
 * @codeCoverageIgnore
 */
class VirtualCommand extends Command
{
    /**
     * @throws Exception
     */
    final public function execute()
    {
        throw new Exception("You should not see this, if you see this, it means you didn't load the ~/.phpbrew/bashrc script, please check if bashrc is sourced in your shell.");
    }
}
