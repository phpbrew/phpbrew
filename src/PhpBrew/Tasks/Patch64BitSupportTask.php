<?php
namespace PhpBrew\Tasks;

use PhpBrew\Build;
use PhpBrew\Utils;

class Patch64BitSupportTask extends BaseTask
{

    public function match(Build $build)
    {
        // parse version from something like "php-5.3.2..."
        $currentVersion = preg_replace('/[^\d]*(\d+).(\d+).*/i', '$1.$2', $build->version);
        return (Utils::support64bit() && version_compare($currentVersion, '5.3', '=='));
    }

    public function patch(Build $build)
    {
        $this->info("===> Checking if php-5.3 source is on 64bit machine");

        if ($this->match($build)) {
            // Then patch Makefile for PHP 5.3.x on 64bit system.
            $this->info("===> Applying patch file for php5.3.x on 64bit machine.");

            if (!$this->options->dryrun) {
                $this->logger->debug('sed -i.bak \'/^BUILD_/ s/\$(CC)/\$(CXX)/g\' Makefile');
                system('sed -i.bak \'/^BUILD_/ s/\$(CC)/\$(CXX)/g\' Makefile');

                $this->logger->debug('sed -i.bak \'/EXTRA_LIBS = /s|$| -lstdc++|\' Makefile');
                system('sed -i.bak \'/EXTRA_LIBS = /s|$| -lstdc++|\' Makefile');
            }
        }
    }
}
