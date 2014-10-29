<?php
namespace PhpBrew\Command\ExtCommand;
use CLIFramework\Command;
use PhpBrew\Command\AbstractConfigCommand;
use PhpBrew\Extension;
use PhpBrew\Extension\ExtensionFactory;
use PhpBrew\Extension\ExtensionManager;

class CleanCommand extends Command
{
    public function brief()
    {
        return 'phpbrew ext clean [extension name]';
    }

    public function execute($extensionName)
    {
        if ($ext = ExtensionFactory::lookup($extensionName)) {
            $this->logger->info("Cleaning $extensionName...");
            $manager = new ExtensionManager($this->logger);
            $manager->cleanExtension($ext);
            $this->logger->info("Done");
        }
    }
}
