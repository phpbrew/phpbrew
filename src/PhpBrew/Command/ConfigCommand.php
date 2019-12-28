<?php

namespace PhpBrew\Command;

use CLIFramework\Command;
use PhpBrew\Config;
use PhpBrew\Utils;

class ConfigCommand extends Command
{
    public function brief()
    {
        return 'Edit your current php.ini in your favorite $EDITOR';
    }

    public function execute()
    {
        $file = $this->getCurrentPhpLoadedIniFile();
        if (!file_exists($file)) {
            $php = Config::getCurrentPhpName();
            $this->logger->warn("Sorry, I can't find the {$file} file for php {$php}.");

            return;
        }

        Utils::editor($file);
    }

    private function getCurrentPhpLoadedIniFile()
    {
        $cmd  = Config::getCurrentPhpBin() . '/php';
        $options = ' -r ';
        $code = '"echo php_ini_loaded_file();"';

        exec($cmd . $options . $code, $output, $retVal);
        if ($retVal) {
            return false;
        }

        $file = $output[0];

        return $file;
    }
}
