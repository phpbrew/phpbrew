<?php
namespace PhpBrew\Tasks;
use PhpBrew\Config;
use PhpBrew\PhpSource;

class PrepareDirectoryTask extends BaseTask
{

    public function prepareForVersion($version)
    {
        $home = Config::getPhpbrewRoot();
        $buildDir = Config::getBuildDir();
        $buildPrefix = Config::getVersionBuildPrefix( $version );

        if( ! file_exists($buildDir) )
            mkdir( $buildDir, 0755, true );
        if( ! file_exists($buildPrefix) )
            mkdir( $buildPrefix, 0755, true );
    }

}
