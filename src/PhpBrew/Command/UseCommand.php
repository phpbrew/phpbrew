<?php
namespace PhpBrew\Command;

use CLIFramework\Command;
use PhpBrew\Config;

/**
 * @codeCoverageIgnore
 */
class UseCommand extends Command
{

    public function arguments($args) {
        $args->add('php version')
            ->validValues(function(){
                return Config::findMatchedBuilds();
            })
            ;
    }

    public function brief()
    {
        return 'Use php, switch version temporarily';
    }

    public function execute($buildName) {
        { // this block is important for tests only
            $root = Config::getPhpbrewRoot();
            $home = Config::getPhpbrewHome();
            putenv("PHPBREW_ROOT=$root");
            putenv("PHPBREW_HOME=$home");
            putenv("PHPBREW_PHP=$buildName");
            putenv("PHPBREW_PATH=$root/$buildName/bin");
            putenv("PHPBREW_BIN=$home/bin");
        }
        $this->logger->warning("You should not see this, if you see this, it means you didn't load the ~/.phpbrew/bashrc script, please check if bashrc is sourced in your shell.");
    }
}
