<?php

namespace PhpBrew\Console\Command;

use Exception;
use PhpBrew\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SelfUpdateCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('purge')
            ->setDescription('self-update, default to master version.')
            ->setDefinition(array(
                new InputArgument('branch', InputArgument::OPTIONAL, 'The branch to use'),
            ));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$branch = $input->getArgument('branch')) {
            $branch = 'master';
        }

        global $argv;
        $script = realpath( $argv[0] );
        if( ! is_writable($script) ) {
            throw new Exception("$script is not writable.");
        }

        // fetch new version phpbrew
        $this->logger->info("Updating phpbrew $script from $branch...");

        $url = "https://raw.github.com/c9s/phpbrew/$branch/phpbrew";
        system("curl -# -L $url > $script") == 0 or die('Update failed.');

        $this->logger->info("Version updated.");
        system( $script . ' init' );
        system( $script . ' --version' );
    }
}




