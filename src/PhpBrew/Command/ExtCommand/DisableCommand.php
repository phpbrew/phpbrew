<?php
namespace PhpBrew\Command\ExtCommand;
use PhpBrew\Extension;
use PhpBrew\Extension\ExtensionManager;;

class DisableCommand extends \CLIFramework\Command
{
    public function usage()
    {
        return 'phpbrew ext disable [extension name]';
    }

    public function brief()
    {
        return 'Disable PHP extension';
    }

    public function execute($extname)
    {
        $ext = new Extension($extname);
        $manager = new ExtensionManager($this->logger);
        $manager->disable($ext);
    }
}
