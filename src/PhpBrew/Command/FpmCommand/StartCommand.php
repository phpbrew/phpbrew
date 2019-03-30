<?php

namespace PhpBrew\Command\FpmCommand;

use PhpBrew\Command\VirtualCommand;

class StartCommand extends VirtualCommand
{
    public function brief()
    {
        return 'Start FPM server';
    }
}
