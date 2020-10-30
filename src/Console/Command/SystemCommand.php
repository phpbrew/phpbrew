<?php

namespace PHPBrew\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class SystemCommand extends Command
{
    protected static $defaultName = 'system';

    protected function configure()
    {
        $this
            ->setDescription('Get or set the internally used PHP binary')
            ->addArgument('PHP version', InputArgument::OPTIONAL)
        ;
    }

    /**
     * {@inheritDoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $path = getenv('PHPBREW_SYSTEM_PHP');

        if ($path !== false && $path !== '') {
            $output->writeln($path);
        }
    }
}
