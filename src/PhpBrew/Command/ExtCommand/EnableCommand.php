<?php
namespace PhpBrew\Command\ExtCommand;
use PhpBrew\Extension;

class EnableCommand extends \CLIFramework\Command
{
    public function usage() { return 'phpbrew ext enable [extension name]'; }

    public function brief() { return 'Enable PHP extension'; }

    public function execute($extname)
    {
        (new Extension($extname, $this->logger))->enable();
    }
}
