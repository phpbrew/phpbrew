<?php
namespace PhpBrew\Command;
use DOMDocument;
use PhpBrew\PhpSource;

class KnownCommand extends \CLIFramework\Command
{
    public function brief() { return 'list known PHP versions'; }

    public function options($opts) 
    {
        $opts->add('svn','list subversion phps');
        $opts->add('old','list old phps (less than 5.3)');
        $opts->add('stas','list stas phps');
    }

    public function execute()
    {
        $stableVersions = PhpSource::getStableVersions( $this->options->old );
        echo "Available stable versions:\n";
        foreach( $stableVersions as $version => $arg ) {
            echo "\t" . $version . "\n";
        }

        if( $this->options->svn ) {
            $svnVersions = \PhpBrew\PhpSource::getSvnVersions();
            echo $this->formatter->format("Available svn versions:\n",'yellow');
            foreach( $svnVersions as $version => $arg ) {
                echo "\t" . $version . "\n";
            }
        }

        if( $this->options->stas ) {
            $versions = \PhpBrew\PhpSource::getStasVersions();
            echo $this->formatter->format("Available versions from PhpStas:\n",'yellow');
            foreach( $versions as $version => $arg ) {
                echo "\t" . $version . "\n";
            }
        }
    }
}


