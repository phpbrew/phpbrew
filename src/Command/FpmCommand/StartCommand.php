<?php

namespace PHPBrew\Command\FpmCommand;

use PHPBrew\Command\VirtualCommand;

class StartCommand extends VirtualCommand
{
    public function brief()
    {
        return 'Start FPM server';
    }
}
