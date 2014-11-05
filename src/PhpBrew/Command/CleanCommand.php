<?php
namespace PhpBrew\Command;
use PhpBrew\Tasks\CleanTask;
use PhpBrew\Build;
use PhpBrew\Config;
use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use CLIFramework\Command;

class CleanCommand extends Command
{
    public function brief()
    {
        return 'Clean up the source directory of a PHP distribution';
    }

    public function usage()
    {
        return 'phpbrew clean [-a|--all] [php-version]';
    }

    public function options($opts)
    {
        $opts->add('a|all', 'Remove all the files in the source directory of the PHP distribution.');
    }

    public function arguments($args) {
        $args->add('installed php')
            ->validValues(function() { return \PhpBrew\Config::getInstalledPhpVersions(); })
            ;
    }

    public function execute($version)
    {
        if ($this->options->all) {
            $buildDir = Config::getBuildDir() . DIRECTORY_SEPARATOR . $version;

            if (file_exists($buildDir)) {
                $this->logger->info("Source directory " . $buildDir . " found, deleting...");
                $directoryIterator = new RecursiveDirectoryIterator($buildDir, RecursiveDirectoryIterator::SKIP_DOTS);
                $it = new RecursiveIteratorIterator($directoryIterator, RecursiveIteratorIterator::CHILD_FIRST);
                foreach ($it as $file) {
                    $this->logger->debug($file->getPathname());
                    if ($file->isDir()) {
                        rmdir($file->getPathname());
                    } else {
                        unlink($file->getPathname());
                    }
                }
            } else {
                $this->logger->info("Source directory " . $buildDir . " not found.");
            }
        } else {
            $clean = new CleanTask($this->logger);
            $build = new Build($version);
            if ($clean->clean($build)) {
                $this->logger->info("Distribution is cleaned up. Woof! ");
            }
        }
    }
}
