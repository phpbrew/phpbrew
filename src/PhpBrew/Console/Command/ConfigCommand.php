<?php

namespace PhpBrew\Console\Command;

use PhpBrew\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('config')
            ->setDefinition(array(
                new InputArgument('name', InputArgument::REQUIRED),
            ));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');

        switch($name)
        {
        case 'home':
            $output->writeln(Config::getPhpbrewRoot());
            break;
        case 'build':
            $output->writeln(Config::getBuildDir());
            break;
        case 'bin':
            $output->writeln(Config::getCurrentPhpBin());
            break;
        case 'include':
            $output->writeln(Config::getVersionBuildPrefix( Config::getCurrentPhpName() ) .
                    DIRECTORY_SEPARATOR . 'include');
            break;
        case 'etc':
            $output->writeln(Config::getVersionBuildPrefix( Config::getCurrentPhpName() ) .
                    DIRECTORY_SEPARATOR . 'etc');
            break;
        }
    }
}


