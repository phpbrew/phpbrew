<?php
namespace PhpBrew\Command;
use PhpBrew\Config;

class ListCommand extends \CLIFramework\Command
{
    public function brief() { return 'list installed PHP versions'; }

    public function execute()
    {
        $versions = \PhpBrew\Config::getInstalledPhpVersions();

        // var_dump( $versions ); 
        echo "Installed versions:\n";
        foreach( $versions as $version ) {
            echo "\t" . $version . "\n";
        }
    }
}


