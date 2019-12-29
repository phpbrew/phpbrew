<?php

namespace PhpBrew\Command;

class ConfigCommand extends VirtualCommand
{
    public function brief()
    {
        return 'Edit your current php.ini in your favorite $EDITOR';
    }
}
