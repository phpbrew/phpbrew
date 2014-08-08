<?php
namespace PhpBrew\Command\ExtCommand;

use PhpBrew\Command\AbstractConfigCommand;
use PhpBrew\Extension;

class ConfigCommand extends AbstractConfigCommand
{
    public function brief()
    {
        return 'phpbrew ext config [extension name]';
    }

    public function execute($extname)
    {
        $extension = new Extension($extname, $this->logger);
        $file = $extension->getMeta()->getIniFile();
        if(! file_exists($file)) $file .= '.disabled';
        $this->editor($file);
    }
}
