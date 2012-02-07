<?php
namespace PhpBrew\Command;
use PhpBrew\Config;

class EnvCommand extends \CLIFramework\Command
{
    public function brief() { return 'export environment variables'; }

    public function execute($version)
    {

        $root = Config::getPhpbrewRoot();
        $buildDir = Config::getBuildDir();
        $versionBuildPrefix = Config::getVersionBuildPrefix($version);
        $versionBinPath     = Config::getVersionBinPath($version);

        echo 'PHPBREW_PHP=' . $version . "\n";
        echo 'PHPBREW_HOME=' . $root . "\n";
        echo 'PHPBREW_PATH=' . $versionBinPath . "\n";
    }
}






