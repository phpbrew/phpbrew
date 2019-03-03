<?php

namespace PhpBrew\Command;

use PhpBrew\BuildFinder;

/**
 * @codeCoverageIgnore
 */
class SwitchCommand extends VirtualCommand
{
    public function arguments($args)
    {
        $args->add('installed php')
            ->validValues(function () {
                return BuildFinder::findMatchedBuilds();
            })
            ;
    }

    public function brief()
    {
        return 'Switch default php version.';
    }
}
