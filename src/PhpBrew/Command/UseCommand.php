<?php
namespace PhpBrew\Command;
use CLIFramework\Command;
use PhpBrew\Config;
use Exception;

class UseCommand extends Command
{

    public function arguments($args) {
        $args->add('php version')
            ->validValues(function() {
                return array_merge(\PhpBrew\Config::getInstalledPhpVersions(), array('latest')); 
            })
            ;
    }

    public function brief()
    {
        return 'Use php, switch version temporarily';
    }

    public function execute($buildName) {
        $root = Config::getPhpbrewRoot();
        $home = Config::getPhpbrewHome();

        $foundBuildName = null;
        if (strtolower(trim($buildName)) == 'latest') {
            $foundBuildName = Config::findLatestBuild(false);
        } else {
            $foundBuildName = Config::findFirstMatchedBuild($buildName, false);
        }

        $phpDir = $root . DIRECTORY_SEPARATOR . 'php';
        if (!$foundBuildName) {
            // TODO: list possible build names here...
            throw new Exception("$buildName does not exist in $phpDir directory.");
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
