<?php

namespace PhpBrew\Command;

use CLIFramework\Command as BaseCommand;
use PhpBrew\BuildFinder;
use PhpBrew\Config;

class EnvCommand extends BaseCommand
{
    public function brief()
    {
        return 'Export environment variables';
    }

    public function arguments($args)
    {
        $args->add('PHP build')
            ->optional()
            ->validValues(function () {
                return BuildFinder::findInstalledBuilds();
            })
            ;
    }

    public function execute($buildName = null)
    {
        // get current version
        if (!$buildName) {
            $buildName = getenv('PHPBREW_PHP');
        }

        $this->export('PHPBREW_ROOT', Config::getRoot());
        $this->export('PHPBREW_HOME', Config::getHome());

        $this->replicate('PHPBREW_LOOKUP_PREFIX');

        if ($buildName !== false) {
            $targetPhpBinPath = Config::getVersionBinPath($buildName);

            // checking php version existence
            if (is_dir($targetPhpBinPath)) {
                $this->export('PHPBREW_PHP', $buildName);
                $this->export('PHPBREW_PATH', $targetPhpBinPath);
            }
        }

        $this->replicate('PHPBREW_SYSTEM_PHP');

        $this->logger->writeln('# Run this command to configure your shell:');
        $this->logger->writeln('# eval "$(phpbrew env)"');
    }

    private function export($varName, $value)
    {
        $this->logger->writeln(sprintf('export %s=%s', $varName, $value));
    }

    private function replicate($varName)
    {
        $value = getenv($varName);

        if ($value !== false && $value !== '') {
            $this->export($varName, $value);
        }
    }
}
