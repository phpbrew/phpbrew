<?php
namespace PhpBrew\Command;

/**
 * @codeCoverageIgnore
 */
class SwitchCommand extends VirtualCommand
{

    public function arguments($args) {
        $args->add('installed php')
            ->validValues(function() {
                return \PhpBrew\Config::findMatchedBuilds('', true);
            })
            ;
    }


    public function brief()
    {
        return 'Switch default php version.';
    }

    public function execute($version = null) {
        $this->logger->warning("You should not see this, if you see this, it means you didn't load the ~/.phpbrew/bashrc script, please check if bashrc is sourced in your shell.");
    }
}
