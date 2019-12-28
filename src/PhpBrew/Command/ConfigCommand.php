<?php

namespace PhpBrew\Command;

use CLIFramework\Command;
use PhpBrew\Config;
use PhpBrew\Utils;
use PhpBrew\UsePhpFunctionWrapper;

class ConfigCommand extends Command
{
    public function brief()
    {
        return 'Edit your current php.ini in your favorite $EDITOR';
    }

    public function execute()
    {
        UsePhpFunctionWrapper::execute('php_ini_loaded_file()', $output) ? $file = $output : $file = '';
        if (!file_exists($file)) {
            $php = Config::getCurrentPhpName();
            $this->logger->warn("Sorry, I can't find the {$file} file for php {$php}.");

            return;
        }

        Utils::editor($file);
    }
}
