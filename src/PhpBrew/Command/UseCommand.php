<?php
namespace PhpBrew\Command;
use CLIFramework\Command;
use PhpBrew\Config;

class UseCommand extends Command
{

    public function arguments($args) {
        $args->add('installed php')
            ->validValues(function() { return \PhpBrew\Config::getInstalledPhpVersions(); })
            ;
    }

    public function brief()
    {
        return 'Use php, switch version temporarily';
    }

    public function execute($buildName) {
        $root = Config::getPhpbrewRoot();
        $home = Config::getPhpbrewHome();
        putenv("PHPBREW_ROOT=$root");
        putenv("PHPBREW_HOME=$home");
        putenv("PHPBREW_PHP=$buildName");
        putenv("PHPBREW_PATH=$root/$buildName/bin");
        $this->logger->warning("You should not see this, if you see this, it means you didn't load the ~/.phpbrew/bashrc script, please check if bashrc is sourced in your shell.");
    }
}
