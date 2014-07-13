<?php
namespace PhpBrew\Command;

use CLIFramework\Command;

class CdCommand extends VirtualCommand
{
    public function brief()
    {
        return 'Change to directories';
    }

    public function arguments($args) {
        $args->add('directory')
            ->isa('string')
            ->validValues(explode('|', 'var|etc|build|dist'))
            ;
    }

    public function usage()
    {
        return 'phpbrew cd [var|etc|build|dist]';
    }
}
