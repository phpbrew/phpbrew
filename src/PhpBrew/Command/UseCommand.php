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
        $args->add('PHP version')
            ->validValues(function () {
                return BuildFinder::findInstalledVersions();
            })
            ;
    }

    public function brief()
    {
        return 'Use php, switch version temporarily';
    }
}
