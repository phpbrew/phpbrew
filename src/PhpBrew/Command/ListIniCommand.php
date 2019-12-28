<?php

namespace PhpBrew\Command;

use CLIFramework\Command;
use PhpBrew\UsePhpFunctionWrapper;

class ListIniCommand extends Command
{
    public function brief()
    {
        return 'List loaded ini config files.';
    }

    public function execute()
    {
        UsePhpFunctionWrapper::execute('php_ini_scanned_files()', $output)
            ? $filelist = $output
            : $filelist = '';
        if ($filelist) {
            echo "Loaded ini files:\n";
            if (strlen($filelist) > 0) {
                $files = explode(',', $filelist);
                foreach ($files as $file) {
                    echo ' - ' . trim($file) . "\n";
                }
            }
        }
    }
}
