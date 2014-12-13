<?php
namespace PhpBrew\Tasks;
use PhpBrew\Buildable;
use PhpBrew\Config;
use PhpBrew\Utils;

/**
 * Task to run `make clean`
 */
class MakeTask extends BaseTask
{
    private $buildLogPath;
    private $isQuiet = false;

    public function run(Buildable $build) {
        return $this->make($build->getSourceDirectory(), 'all');
    }

    public function install(Buildable $build)
    {
        return $this->make($build->getSourceDirectory(), 'install');
    }

    public function clean(Buildable $build)
    {
        return $this->make($build->getSourceDirectory(), 'clean');
    }

    public function setBuildLogPath($buildLogPath)
    {
        $this->buildLogPath = $buildLogPath;
    }

    public function setQuiet()
    {
        $this->isQuiet = true;
    }

    public function isQuiet()
    {
        return $this->isQuiet;
    }

    private function make($path, $target = 'all')
    {
        if (!file_exists($path . DIRECTORY_SEPARATOR . 'Makefile')) {
            $this->logger->error("Makefile not found");
            return false;
        }
        $this->logger->info("===> Make $target");
        $cmd = array(
            "make",
            "-C", $path,
            $this->isQuiet() ? "--quiet" : "",
            $target
        );
        if (!$this->logger->isDebug() && $this->buildLogPath) {
            $cmd[] = " >> $this->buildLogPath 2>&1";
        }
        $ret = Utils::system($cmd, $this->logger);
        return $ret == 0;
    }
}
