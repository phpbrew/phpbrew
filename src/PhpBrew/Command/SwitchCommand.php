<?php
namespace PhpBrew\Command;

use PhpBrew\BuildFinder;
use PhpBrew\Config;

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

    public function execute($version = null)
    {
        $this->logger->warning("You should not see this, if you see this, it means you didn't load the ~/.phpbrew/bashrc script, please check if bashrc is sourced in your shell.");
    }
}
