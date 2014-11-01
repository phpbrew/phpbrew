<?php
namespace PhpBrew\Command\ExtensionCommand;
use CLIFramework\Command;
use PhpBrew\Command\AbstractConfigCommand;
use PhpBrew\Extension;
use PhpBrew\Extension\ExtensionFactory;
use PhpBrew\Extension\ExtensionManager;

class CleanCommand extends Command
{
    public function brief()
    {
        return 'Clean up the compiled objects in the extension source directory.';
    }

    public function options($opts)
    {
        $opts->add('p|purge', 'Remove all the source files.');
    }

    public function execute($extensionName)
    {
        if ($ext = ExtensionFactory::lookup($extensionName)) {
            $this->logger->info("Cleaning $extensionName...");
            $manager = new ExtensionManager($this->logger);

            if ($this->options->purge) {
                $manager->purgeExtension($ext);
            } else {
                $manager->cleanExtension($ext);
            }
            $this->logger->info("Done");
        }
    }
}
