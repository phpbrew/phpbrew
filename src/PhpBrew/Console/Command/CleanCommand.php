<?php

namespace PhpBrew\Console\Command;

use Exception;
use PhpBrew\Config;
use PhpBrew\PhpSource;
use PhpBrew\CommandBuilder;
use PhpBrew\Tasks\CleanTask;
use PhpBrew\DirectorySwitch;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CleanCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('clean')
            ->setDescription('Clean up php distribution.')
            ->setDefinition(array(
                new InputArgument('version', InputArgument::REQUIRED, 'The php version to clean'),
            ));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $version = $input->getArgument('version');

        if( ! preg_match('/^php-/', $version) )
            $version = 'php-' . $version;

        $info = PhpSource::getVersionInfo( $version, $this->options->old );
        if( ! $info)
            throw new Exception("Version $version not found.");

        $clean = new CleanTask($this->logger);
        if( $clean->cleanByVersion($version) ) {
            $this->logger->info("Distribution is cleaned up. Woof! ");
        }
    }
}
