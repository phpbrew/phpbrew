<?php
namespace PhpBrew\Command;
use PhpBrew\Config;
use Exception;
use PhpBrew\Utils;

class EnvCommand extends \CLIFramework\Command
{
    public function brief() { return 'export environment variables'; }

    public function execute($version = null)
    {
        // get current version
        if (! $version) {
            $version = getenv('PHPBREW_PHP');
        }

        // $currentVersion;
        $root = Config::getPhpbrewRoot();
        $home = Config::getPhpbrewHome();
        $lookup = getenv('PHPBREW_LOOKUP_PREFIX');

        // $versionBuildPrefix = Config::getVersionBuildPrefix($version);
        // $versionBinPath     = Config::getVersionBinPath($version);

        echo "export PHPBREW_ROOT=$root\n";
        echo "export PHPBREW_HOME=$home\n";
        echo "export PHPBREW_LOOKUP_PREFIX=$lookup\n";

        if ($version !== false) {
            // checking php version exists
            $version = Utils::findLatestPhpVersion($version);
            $targetPhpBinPath = Config::getVersionBinPath($version);
            if (!is_dir($targetPhpBinPath)) {
                throw new Exception("# php version: " . $version . " not exists.");
            }
            echo 'export PHPBREW_PHP='  . $version . "\n";
            echo 'export PHPBREW_PATH=' . ($version ? Config::getVersionBinPath($version) : '') . "\n";
        }

    }

}
