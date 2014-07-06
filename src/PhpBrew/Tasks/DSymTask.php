<?php
namespace PhpBrew\Tasks;

use PhpBrew\Config;

class DSymTask extends BaseTask
{

    // Fix php.dSYM
    /* Check if php.dSYM exists */
    /**
     * @param  \PhpBrew\Build $build
     * @return bool
     */
    public function check($build)
    {
        $phpbin = $build->getBinDirectory() . DIRECTORY_SEPARATOR . 'php';
        $dSYM = $build->getBinDirectory() . DIRECTORY_SEPARATOR . 'php.dSYM';

        return !file_exists($phpbin) && file_exists($dSYM);
    }

    public function patch($build, $options)
    {
        if ($this->check($build)) {
            $this->logger->info("---> Moving php.dSYM to php ");

            if (!$options->dryrun) {
                $dSYM = $build->getBinDirectory() . DIRECTORY_SEPARATOR . 'php.dSYM';

                $buildPrefix = Config::getBuildPrefix();
                $php = $buildPrefix . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'php';
                rename($dSYM, $php);
            }
        }
    }
}
