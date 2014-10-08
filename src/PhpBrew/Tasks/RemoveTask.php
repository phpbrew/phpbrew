<?php
namespace PhpBrew\Tasks;

use PhpBrew\Config;

class RemoveTask extends BaseTask
{
    public function remove($path, $verbose = false)
    {
        // Should do this very carefully.
        if (file_exists($path) && $path != '/') {
            if ($verbose) {
                system("rm -rvf $path");
            } else {
                system("rm -rf $path");
            }
        }
    }

    public function removeByVersion($version, $verbose = false)
    {
        $home = Config::getPhpbrewRoot();
        $buildPrefix = Config::getVersionInstallPrefix($version);
        $this->remove($buildPrefix);
    }

    /**
     *
     * @param string $buildId a build ID is a version string that followed by
     *                        variants and options.
     */
    public function removeByBuildId($buildId)
    {
        // XXX:
    }
}
