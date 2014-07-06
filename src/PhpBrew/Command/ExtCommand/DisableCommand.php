<?php

namespace PhpBrew\Command\ExtCommand;

use PhpBrew\Extension;

class DisableCommand extends \CLIFramework\Command
{
    public function usage() { return 'phpbrew ext disable [extension name]'; }

    public function brief() { return 'Disable PHP extension'; }

    public function execute($extname)
    {
        $extension = new Extension($extname, $this->logger);
        $extension->disable();
    }
}
