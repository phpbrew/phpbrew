<?php
namespace PhpBrew\Command\ExtCommand;
use PhpBrew\Utils;
use PhpBrew\Config;
use CLIFramework\Command;
use Exception;

class DisableCommand extends Command
{
    public function usage() { return 'phpbrew ext disable [extension name]'; }

    public function brief() { return 'Disable PHP extension'; }

    public function execute($name)
    {
        (new Extension($extension_name, $this->logger))->disable();
    }
}
