<?php
namespace PhpBrew\Command;
use CLIFramework\Command;
use PhpBrew\Config;
use Exception;

class UseCommand extends Command
{

    public function arguments($args) {
        $args->add('php version')
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
        $buildDir = $root . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . $buildName;

        $foundBuildName = Config::findFirstMatchedBuild($buildName);
        if (!$foundBuildName) {
            // TODO: list possible build names here...
            throw new Exception("$buildName does not exist in $buildDir directory.");
        }

        $this->logger->info("Found $foundBuildName, setting environment variables...");
        // update environment
        putenv("PHPBREW_ROOT=$root");
        putenv("PHPBREW_HOME=$home");
        putenv("PHPBREW_PHP=$foundBuildName");
        Config::putPathEnvFor($foundBuildName);


        $this->logger->warning("You should not see this, if you see this, it means you didn't load the ~/.phpbrew/bashrc script, please check if bashrc is sourced in your shell.");
    }
}
