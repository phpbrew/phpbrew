<?php

namespace PHPBrew\Command\AppCommand;

use CLIFramework\Command;
use PHPBrew\AppStore;

class ListCommand extends Command
{
    public function brief()
    {
        return '[deprecated] List PHP applications';
    }

    public function execute()
    {
        $this->logger->warn(
            'The app command and its subcommands are deprecated and will be removed in the future.' . PHP_EOL
            . 'Please consider switching to PHIVE (https://phar.io/).'
        );

        $apps = AppStore::all();
        foreach ($apps as $name => $opt) {
            $this->logger->writeln(sprintf('% -8s - %s', $name, $opt['url']));
        }
    }
}
