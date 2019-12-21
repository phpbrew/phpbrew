<?php

namespace PHPBrew\Tasks;

use PHPBrew\Build;

class DSymTask extends BaseTask
{
    // Fix php.dSYM
    /* Check if php.dSYM exists */
    /**
     * @param Build $build
     *
     * @return bool
     */
    public function check(Build $build)
    {
        $phpbin = $build->getBinDirectory() . DIRECTORY_SEPARATOR . 'php';
        $dSYM = $build->getBinDirectory() . DIRECTORY_SEPARATOR . 'php.dSYM';

        return !file_exists($phpbin) && file_exists($dSYM);
    }

    public function patch(Build $build, $options)
    {
        if ($this->check($build)) {
            $this->logger->info('---> Moving php.dSYM to php ');
            if (!$options->dryrun) {
                $phpBin = $build->getBinDirectory() . DIRECTORY_SEPARATOR . 'php';
                $dSYM = $build->getBinDirectory() . DIRECTORY_SEPARATOR . 'php.dSYM';
                rename($dSYM, $phpBin);
            }
        }
    }
}
