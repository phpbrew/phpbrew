<?php

namespace PhpBrew\Console\Command;

use DOMDocument;
use PhpBrew\PhpSource;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class KnownCommand extends Command
{
    /* public function options($opts)
    {
        $opts->add('svn','list subversion phps');
        $opts->add('old','list old phps (less than 5.3)');
        $managers = PhpSource::getReleaseManagers();
        foreach($managers as $id => $fullName) {
            $opts->add($id,"list $id phps");
        }
    } */

    protected function configure()
    {
        $this
            ->setName('known')
            ->setDescription('List known PHP versions.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
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

        $managers = PhpSource::getReleaseManagers();
        foreach($managers as $id => $fullName) {
            if( $this->options->$id ) {
                $versions = \PhpBrew\PhpSource::getReleaseManagerVersions($id);
                echo $this->formatter->format(
                    "Available versions from PHP Release Manager: $fullName\n",
                    'yellow');
                foreach( $versions as $version => $arg ) {
                    echo "\t" . $version . "\n";
                }
            }
        }
    }
}


