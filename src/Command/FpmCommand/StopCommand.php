<?php

namespace PHPBrew\Command\FpmCommand;

use PHPBrew\Command\VirtualCommand;

class StopCommand extends VirtualCommand
{
    public function brief()
    {
        return 'Stop FPM server';
    }
}
