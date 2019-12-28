<?php

namespace PhpBrew\Command;

use CLIFramework\Command;
use PhpBrew\Config;

class ListIniCommand extends Command
{
    public function brief()
    {
        return 'List loaded ini config files.';
    }

    public function execute()
    {
        if ($files = $this->getCurrentPhpScannedIniFiles()) {
            echo "Loaded ini files:\n";
            if (count($files) > 0) {
                foreach ($files as $file) {
                    echo ' - ' . $file . "\n";
                }
            }
        }
    }

    private function getCurrentPhpScannedIniFiles()
    {
        $cmd  = Config::getCurrentPhpBin() . '/php';
        $options = ' -r ';
        $code = '"echo php_ini_scanned_files();"';

        exec($cmd . $options . $code, $output, $retVal);
        if ($retVal) {
            return false;
        }

        foreach ($output as $file) {
            $files[] = str_replace(',', '', $file);
        }

        return $files;
    }
}
