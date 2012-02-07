<?php
namespace PhpBrew\Command;
use PhpBrew\Config;

class EnvCommand extends \CLIFramework\Command
{
    public function brief() { return 'export environment variables'; }

    public function execute($version = null)
    {
        // get current version
        if( ! $version )
            $version = getenv('PHPBREW_PHP');

        // $currentVersion;
        $root = Config::getPhpbrewRoot();
        $buildDir = Config::getBuildDir();

        // $versionBuildPrefix = Config::getVersionBuildPrefix($version);
        // $versionBinPath     = Config::getVersionBinPath($version);

        // current version
        if( getenv('PHPBREW_PHP') ) {
            echo 'export PHPBREW_PHP='  . getenv('PHPBREW_PHP') . "\n";
        }

        echo 'export PHPBREW_ROOT=' . $root . "\n";

        if( $version )
            echo 'export PHPBREW_PATH=' . Config::getVersionBinPath($version) . "\n";
    }

}
