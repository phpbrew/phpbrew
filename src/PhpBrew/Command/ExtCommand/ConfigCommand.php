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
        $this->logger->info("Looking for {$file} file...");
        if(! file_exists($file)) {
            $file .= '.disabled'; // try with ini.disabled file
            $this->logger->info("Looking for {$file} file...");
            if(! file_exists($file)) {
                $this->logger->warn("Sorry, I can't find the ini file for the requested extension: \"{$extname}\".");
                return;
            }
        }
        $this->editor($file);
    }
}
