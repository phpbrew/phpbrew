<?php
namespace PhpBrew\Command;

/**
 * @codeCoverageIgnore
 */

class SwitchCommand extends VirtualCommand
{

    public function arguments($args) {
        $args->add('installed php')
            ->validValues(function() { return \PhpBrew\Config::getInstalledPhpVersions(); })
            ;
    }


    public function brief()
    {
        return 'Switch default php version.';
    }
}
