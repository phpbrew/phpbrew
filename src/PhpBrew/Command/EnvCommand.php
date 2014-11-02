<?php
namespace PhpBrew\Command;

use PhpBrew\Config;
use Exception;
use PhpBrew\Utils;

class EnvCommand extends \CLIFramework\Command
{
    public function brief()
    {
        return 'Export environment variables';
    }

    public function execute($buildName = NULL)
    {
        // get current version
        if (!$buildName) {
            $buildName = getenv('PHPBREW_PHP');
        }

        // $currentVersion;
        $root = Config::getPhpbrewRoot();
        $home = Config::getPhpbrewHome();
        $lookup = getenv('PHPBREW_LOOKUP_PREFIX');

        $this->logger->writeln("export PHPBREW_ROOT=$root");
        $this->logger->writeln("export PHPBREW_HOME=$home");
        $this->logger->writeln("export PHPBREW_LOOKUP_PREFIX=$lookup");

        if ($buildName !== false) {
            // checking php version existence
            $targetPhpBinPath = Config::getVersionBinPath($buildName);
            if (is_dir($targetPhpBinPath)) {
                echo 'export PHPBREW_PHP=' . $buildName . "\n";
                echo 'export PHPBREW_PATH=' . ($buildName ? Config::getVersionBinPath($buildName) : '') . "\n";
            }
        }

    }
}
