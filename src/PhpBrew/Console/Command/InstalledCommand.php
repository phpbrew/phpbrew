<?php

namespace PhpBrew\Console\Command;

use PhpBrew\Config;
use PhpBrew\VariantParser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InstalledCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('installed')
            ->setDescription('List installed PHP versions.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $versions = Config::getInstalledPhpVersions();
        $currentVersion = Config::getCurrentPhpName();

        // var_dump( $versions );
        $output->writeln("Installed versions:");

        if ( $currentVersion === false or in_array($currentVersion, $versions) === false ) {
            $output->writeln("* (system)");
        }

        foreach( $versions as $version ) {
            $versionPrefix = Config::getVersionBuildPrefix($version);

            $output->writeln(sprintf('  %-15s  (%-10s)', $version, $versionPrefix));

            if( file_exists($versionPrefix . DIRECTORY_SEPARATOR . 'phpbrew.variants') ) {
                $info = unserialize(file_get_contents( $versionPrefix . DIRECTORY_SEPARATOR . 'phpbrew.variants'));

                $output->writeln(str_repeat(' ', 19));
                $output->writeln(VariantParser::revealCommandArguments($info));
            }
        }
    }
}


