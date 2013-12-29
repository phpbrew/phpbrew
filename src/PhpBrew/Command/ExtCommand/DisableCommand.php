<?php
namespace PhpBrew\Command\ExtCommand;
use PhpBrew\Extension;

class DisableCommand extends \CLIFramework\Command
{
    public function usage() { return 'phpbrew ext disable [extension name]'; }

    public function brief() { return 'Disable PHP extension'; }

    public function execute($extension_name)
    {
        (new Extension($extension_name, $this->logger))->disable();
    }
}
