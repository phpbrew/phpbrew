<?php
namespace PhpBrew\Tasks;
use PhpBrew\Utils;

class Patch64BitSupportTask extends BaseTask
{

    public function match()
    {
        return ( Utils::support_64bit() && $build->compareVersion('5.4') == -1 );
    }

    public function patch()
    {
        // Then patch Makefile for PHP 5.3.x on 64bit system.
        $this->info("===> Applying patch file for php5.3.x on 64bit machine.");
        system('sed -i \'/^BUILD_/ s/\$(CC)/\$(CXX)/g\' Makefile');
        system('sed -i \'/EXTRA_LIBS = /s|$| -lstdc++|\' Makefile');
    }

}


