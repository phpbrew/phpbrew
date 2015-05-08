<?php
namespace PhpBrew\Command;
use CLIFramework\Command;
use PhpBrew\Config;
use Exception;

class UseCommand extends Command
{

    public function arguments($args) {
        $args->add('php version')
            ->validValues(\PhpBrew\Config::findMatchedBuilds('', true));
    }

    public function brief()
    {
        return 'Use php, switch version temporarily';
    }

    public function execute($buildName) {
        $this->logger->warning("You should not see this, if you see this, it means you didn't load the ~/.phpbrew/bashrc script, please check if bashrc is sourced in your shell.");
    }
}
