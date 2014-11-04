<?php
namespace PhpBrew\Command\ExtensionCommand;
use PhpBrew\Extension;
use PhpBrew\Extension\ExtensionManager;
use PhpBrew\Extension\ExtensionFactory;
use PhpBrew\Command\ExtensionCommand\BaseCommand;

class EnableCommand extends BaseCommand
{
    public function usage()
    {
        return 'phpbrew ext enable [extension name]';
    }

    public function brief()
    {
        return 'Enable PHP extension';
    }

    public function execute($extensionName)
    {
        $manager = new ExtensionManager($this->logger);
        $manager->enable($extensionName);
    }
}
