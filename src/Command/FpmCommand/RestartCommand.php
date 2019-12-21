<?php

namespace PHPBrew\Command\FpmCommand;

use PHPBrew\Command\VirtualCommand;

class RestartCommand extends VirtualCommand
{
    public function brief()
    {
        return 'Restart FPM server';
    }
}
