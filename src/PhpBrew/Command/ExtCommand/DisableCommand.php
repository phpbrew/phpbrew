<?php
namespace PhpBrew\Command\ExtCommand;
use PhpBrew\Extension;
use PhpBrew\Extension\ExtensionManager;
use PhpBrew\Extension\ExtensionFactory;
use CLIFramework\Command;

class DisableCommand extends Command
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
        $ext = ExtensionFactory::create($extensionName);
        $manager = new ExtensionManager($this->logger);
        $manager->disable($ext);
    }
}
