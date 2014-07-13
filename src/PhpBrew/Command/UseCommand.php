<?php
namespace PhpBrew\Command;

class UseCommand extends VirtualCommand
{

    public function arguments($args) {
        $args->add('installed php')
            ->validValues(function() { return \PhpBrew\Config::getInstalledPhpVersions(); })
            ;
    }

    public function brief()
    {
        return 'use php, switch version temporarily';
    }
}
