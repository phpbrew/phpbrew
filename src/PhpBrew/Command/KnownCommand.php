<?php
namespace PhpBrew\Command;
use DOMDocument;
use PhpBrew\PhpSource;

class KnownCommand extends \CLIFramework\Command
{
    public function brief() { return 'list known PHP versions'; }

    public function execute()
    {

        $stableVersions = PhpSource::getStableVersions();
        echo "Available stable versions:\n";
        foreach( $stableVersions as $version => $arg ) {
            echo "\t" . $version . "\n";
        }

        $svnVersions = \PhpBrew\PhpSource::getSvnVersions();
        echo "Available svn versions:\n";
        foreach( $svnVersions as $version => $arg ) {
            echo "\t" . $version . "\n";
        }

        $versions = \PhpBrew\PhpSource::getStasVersions();
        echo "Available versions from PhpStas:\n";
        foreach( $versions as $version => $arg ) {
            echo "\t" . $version . "\n";
        }

    }
}


