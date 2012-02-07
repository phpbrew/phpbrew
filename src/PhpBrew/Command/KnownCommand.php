<?php
namespace PhpBrew\Command;
use DOMDocument;

class KnownCommand extends \CLIFramework\Command
{
    public function brief() { return 'list known PHP versions'; }

    public function execute()
    {

        // todo: parse from:
        //    http://snaps.php.net/
        //    http://www.php.net/svn.php # svn

        $versions = \PhpBrew\PhpSource::getStasVersions();

        // var_dump( $versions ); 
        echo "Available versions from PhpStas:\n";
        foreach( $versions as $version => $arg ) {
            echo "\t" . $version . "\n";
        }





    }
}






