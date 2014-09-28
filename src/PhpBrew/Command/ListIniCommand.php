<?php
namespace PhpBrew\Command;
use CLIFramework\Command;

class ListIniCommand extends Command
{
    public function brief()
    {
        return 'List loaded ini config files.';
    }

    public function execute()
    {
        if ($filelist = php_ini_scanned_files()) {
            echo "Loaded ini files:\n";
            if (strlen($filelist) > 0) {
                $files = explode(',', $filelist);
                foreach ($files as $file) {
                    echo " - " . trim($file) . "\n";
                }
            }
        }
    }
}
