<?php
namespace PhpBrew\Tasks;
use PhpBrew\Config;
use PhpBrew\DirectorySwitch;


/**
 * Task to run `make clean`
 */
class CleanTask extends BaseTask
{

    public function clean($path)
    {
        if( ! file_exists( $path . DIRECTORY_SEPARATOR . 'Makefile') ) {
            return false;
        }
        $pwd = getcwd();
        chdir($path);
        system('make clean');
        chdir($pwd);
        return true;
    }

    public function cleanByVersion($version, $verbose = false)
    {
        $home = Config::getPhpbrewRoot();
        $buildPrefix = Config::getVersionBuildPrefix( $version );
        return $this->clean($buildPrefix);
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



