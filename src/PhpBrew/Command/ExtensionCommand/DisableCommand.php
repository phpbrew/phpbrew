<?php
namespace PhpBrew\Command\ExtensionCommand;
use PhpBrew\Extension;
use PhpBrew\Extension\ExtensionManager;
use CLIFramework\Command;
use PhpBrew\Command\ExtensionCommand\BaseCommand;


class DisableCommand extends BaseCommand
{
    public function usage()
    {
        return 'phpbrew ext disable [extension name]';
    }

    public function brief()
    {
        return 'Disable PHP extension';
    }

    public function execute($extensionName)
    {
        $manager = new ExtensionManager($this->logger);
        $manager->disable($extensionName);
    }
}
