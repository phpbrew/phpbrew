<?php
namespace PhpBrew\Tasks;

class DSymTask extends BaseTask
{

    // Fix php.dSYM
    /* Check if php.dSYM exists */
    public function check($build) 
    {
        $phpbin = $build->getBinDirectory() . DIRECTORY_SEPARATOR . 'php';
        $dSYM = $build->getBinDirectory() . DIRECTORY_SEPARATOR . 'php.dSYM';
        return ! file_exists($phpbin) && file_exists($dSYM);
    }

    public function patch($build, $options) 
    {
        if ( $this->check($build) ) {
            $this->logger->info("---> Moving php.dSYM to php ");
            if ( ! $options->dryrun ) {
                $php = $buildPrefix . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'php';
                rename( $dSYM , $php );
            }
        }
    }
}


