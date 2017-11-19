<?php

namespace PhpBrew\Command;

use PhpBrew\BuildFinder;

/**
 * @codeCoverageIgnore
 */
class UseCommand extends VirtualCommand
{
    public function arguments($args)
    {
        $args->add('php version')
            ->validValues(function () {
                return BuildFinder::findInstalledBuilds();
            })
            ;
    }

    public function brief()
    {
        return 'Use php, switch version temporarily';
    }
}
