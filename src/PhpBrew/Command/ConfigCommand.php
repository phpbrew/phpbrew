<?php
namespace PhpBrew\Command;

use CLIFramework\Command;
use PhpBrew\Utils;
use PhpBrew\Config;

class ConfigCommand extends Command
{
    public function brief()
    {
        return 'Edit your current php.ini in your favorite $EDITOR';
    }

    public function execute()
    {
        $file = php_ini_loaded_file();
        if (! file_exists($file)) {
            $php  = Config::getCurrentPhpName();
            $this->logger->warn("Sorry, I can't find the {$file} file for php {$php}.");
            return;
        }

        Utils::editor($file);
    }
}
