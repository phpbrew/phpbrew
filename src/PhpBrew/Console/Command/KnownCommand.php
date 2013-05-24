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
    protected function configure()
    {
        $definitions = array(
            new InputOption('svn', null, InputOption::VALUE_NONE, 'List subversion phps'),
            new InputOption('old', null, InputOption::VALUE_NONE, 'List old phps (less than 5.3)'),
        );

        $managers = PhpSource::getReleaseManagers();
        foreach($managers as $id => $fullName) {
            $definitions[] = new InputOption($id, null, InputOption::VALUE_NONE, "List $id phps");
        }

        $this
            ->setName('known')
            ->setDescription('List known PHP versions.')
            ->setDefinition($definitions);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stableVersions = PhpSource::getStableVersions( $this->options->old );
        $output->writeln('Available stable versions:');
        foreach( $stableVersions as $version => $arg ) {
            $output->writeln("\t" . $version);
        }

        if( $this->options->svn ) {
            $svnVersions = \PhpBrew\PhpSource::getSvnVersions();
            $output->writeln('<comment>Available svn versions:</comment>');
            foreach( $svnVersions as $version => $arg ) {
                $output->writeln("\t" . $version);
            }
        }

        $managers = PhpSource::getReleaseManagers();
        foreach($managers as $id => $fullName) {
            if( $this->options->$id ) {
                $versions = \PhpBrew\PhpSource::getReleaseManagerVersions($id);
                $output->writeln("<comment>Available versions from PHP Release Manager: $fullName</comment>");
                foreach( $versions as $version => $arg ) {
                    $output->writeln("\t" . $version);
                }
            }
        }
    }
}


