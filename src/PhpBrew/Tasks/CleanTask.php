<?php
namespace PhpBrew\Tasks;

use PhpBrew\Config;

/**
 * Task to run `make clean`
 */
class CleanTask extends BaseTask
{
    public function clean($path)
    {
        if (!file_exists($path . DIRECTORY_SEPARATOR . 'Makefile')) {
            return false;
        }

        $this->logger->info('===> Cleaning...');
        $pwd = getcwd();
        chdir($path);
        system('make clean');
        chdir($pwd);

        return true;
    }

    public function cleanByVersion($version, $verbose = false)
    {
        $buildPrefix = Config::getVersionBuildPrefix($version);

        return $this->clean($buildPrefix);
    }

    /**
     *
     * @param string $buildId a build ID is a version string that followed by
     *                        variants and options.
     */
    public function cleanByBuildId($buildId)
    {
        // XXX:
    }
}
