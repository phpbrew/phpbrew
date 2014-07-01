<?php
namespace PhpBrew\Tasks;

use PhpBrew\Utils;

class Patch64BitSupportTask extends BaseTask
{

    public function match($build)
    {
        return ( Utils::support64bit() && $build->compareVersion('5.4') == -1);
    }

    public function patch($build, $options)
    {
        $this->info("===> Checking if php-5.3 source is on 64bit machine");

        if ($this->match($build)) {
            // Then patch Makefile for PHP 5.3.x on 64bit system.
            $this->info("===> Applying patch file for php5.3.x on 64bit machine.");

            if (!$options->dryrun) {
                system('sed -i \'/^BUILD_/ s/\$(CC)/\$(CXX)/g\' Makefile');
                system('sed -i \'/EXTRA_LIBS = /s|$| -lstdc++|\' Makefile');
            }
        }
    }
}
