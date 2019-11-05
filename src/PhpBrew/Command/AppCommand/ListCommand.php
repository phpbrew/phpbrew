<?php

namespace PhpBrew\Command\AppCommand;

use CLIFramework\Command;
use PhpBrew\AppStore;

class ListCommand extends Command
{
    public function brief()
    {
        return 'List PHP applications';
    }

    public function execute()
    {
        $apps = AppStore::all();
        foreach ($apps as $name => $opt) {
            $this->logger->writeln(sprintf('% -8s - %s', $name, $opt['url']));
        }
    }
}
