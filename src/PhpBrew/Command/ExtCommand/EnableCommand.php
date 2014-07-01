<?php
namespace PhpBrew\Command\ExtCommand;

use PhpBrew\Extension;

class EnableCommand extends \CLIFramework\Command
{
    public function usage()
    {
        return 'phpbrew ext enable [extension name]';
    }

    public function brief()
    {
        return 'Enable PHP extension';
    }

    public function execute($extName)
    {
        $extension = new Extension($extName, $this->logger);
        $extension->enable();
    }
}
