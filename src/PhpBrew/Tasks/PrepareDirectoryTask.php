<?php
namespace PhpBrew\Tasks;

use PhpBrew\Config;

class PrepareDirectoryTask extends BaseTask
{
    public function prepareForVersion($version)
    {
        $dirs = array();
        $dirs[] = Config::getPhpbrewRoot();
        $dirs[] = Config::getPhpbrewHome();
        $dirs[] = Config::getBuildDir();
        $dirs[] = Config::getDistFilesDir();
        $dirs[] = Config::getVariantsDir();
        $dirs[] = Config::getVersionInstallPrefix($version);
        foreach($dirs as $dir) {
            if (!file_exists($dir)) {
                $this->logger->debug("Creating directory $dir");
                mkdir($dir, 0755, true);
            }
        }
    }
}
