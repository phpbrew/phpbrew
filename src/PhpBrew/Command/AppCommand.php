<?php

namespace PhpBrew\Command;

use CLIFramework\Command;

class AppCommand extends Command
{
    public function brief()
    {
        return 'php app store';
    }

    public function options($opts)
    {
        $opts->add('l|list', 'Show app list.');
    }

    public function init()
    {
        parent::init();
        $this->command('get');
        $this->command('list');
    }

    public function execute()
    {
        $listCommand = $this->getCommand('list');
        $listCommand->execute();
    }
}
