<?php

namespace PhpBrew\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigratedCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('migrated')
            ->setDescription('This command is migrated.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('- `phpbrew install-ext` command is now moved to `phpbrew ext install`');
        $output->writeln('- `phpbrew enable` command is now moved to `phpbrew ext enable`');
    }

}



