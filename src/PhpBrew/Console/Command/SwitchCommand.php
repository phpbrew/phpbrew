<?php

namespace PhpBrew\Console\Command;

use PhpBrew\Config;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SwitchCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('switch')
            ->setDescription('Switch default php version.')
            ->setDefinition(array(
                new InputArgument('version', InputArgument::OPTIONAL, 'The php version to switch'),
            ));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        throw new Exception("You should not see this, please check if phpbrew bashrc is sourced in your shell.");
    }
}
