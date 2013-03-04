<?php
namespace PhpBrew\Tasks;
use PhpBrew\Config;
use PhpBrew\DirectorySwitch;

class CleanTask extends BaseTask
{

    public function clean($path, $verbose = false)
    {
        // Should do this very carefully.
        if( file_exists($path) && $path != "/" ) {
            if ($verbose) {
                system("rm -rvf $path");
            } else {
                system("rm -rf $path");
            }
        }
    }

    public function cleanByVersion($version, $verbose = false)
    {
        $home = Config::getPhpbrewRoot();
        $buildPrefix = Config::getVersionBuildPrefix( $version );
        $this->clean($buildPrefix);
    }

    /**
     *
     * @param string $buildId a build ID is a version string that followed by 
     * variants and options.
     */
    public function cleanByBuildId($buildId)
    {
        // XXX:
    }

}



