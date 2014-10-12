<?php
namespace PhpBrew\Tasks;
use PhpBrew\Build;
use PhpBrew\Config;

/**
 * Task to run `make clean`
 */
class MakeTask extends BaseTask
{
    public function run(Build $build, $target = '') {
        return $this->makeAt($build->getSourceDirectory());
    }

    public function makeAt($path, $target)
    {
        if (!file_exists($path . DIRECTORY_SEPARATOR . 'Makefile')) {
            return false;
        }
        $this->logger->info("===> Make $target");
        system("make -C $path $target", $ret);
        return $ret == 0;
    }
}
