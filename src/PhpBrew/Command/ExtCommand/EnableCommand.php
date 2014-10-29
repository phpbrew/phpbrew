<?php
namespace PhpBrew\Command\ExtCommand;
use PhpBrew\Extension;
use PhpBrew\Extension\ExtensionManager;
use PhpBrew\Extension\ExtensionFactory;

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

    public function execute($extensionName)
    {
        $manager = new ExtensionManager($this->logger);
        $manager->enable($extensionName);
    }
}
