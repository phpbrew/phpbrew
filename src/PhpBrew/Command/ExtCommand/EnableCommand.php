<?php
namespace PhpBrew\Command\ExtCommand;
use PhpBrew\Extension;
use PhpBrew\Extension\ExtensionManager;;

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
        $ext = new Extension($extName);
        $manager = new ExtensionManager($this->logger);
        $manager->enable($ext);
    }
}
