<?php
namespace PhpBrew\Command;

use Exception;
use PhpBrew\PhpSource;
use PhpBrew\Tasks\CleanTask;

use CLIFramework\Command;

class CleanCommand extends Command
{
    public function brief()
    {
        return 'Clean up php distribution';
    }

    public function usage()
    {
        return 'phpbrew clean [php-version]';
    }

    public function options($opts)
    {
    }

    public function arguments($args) {
        $args->add('installed php')
            ->validValues(function() { return \PhpBrew\Config::getInstalledPhpVersions(); })
            ;
    }

    public function execute($version)
    {
        if (!preg_match('/^php-/', $version)) {
            $version = 'php-' . $version;
        }

        $info = PhpSource::getVersionInfo($version, $this->options->old);

        if (!$info) {
            throw new Exception("Version $version not found.");
        }

        $clean = new CleanTask($this->logger);

        if ($clean->cleanByVersion($version)) {
            $this->logger->info("Distribution is cleaned up. Woof! ");
        }
    }
}
