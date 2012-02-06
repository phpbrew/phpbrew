<?php
namespace PhpBrew\Command;
use DOMDocument;

class KnownCommand extends \CLIFramework\Command
{
    public function brief() { return 'list known PHP versions'; }

    public function execute()
    {
        $versions = \PhpBrew\PhpStas::getVersions();

        // var_dump( $versions ); 
        echo "Available versions:\n";
        foreach( $versions as $version => $link ) {
            echo "\t" . $version . "\n";
        }
    }
}






