<?php
namespace PhpBrew;

class Config
{

    static function getPhpbrewRoot()
    {
        return getenv('HOME') . DIRECTORY_SEPARATOR . '.phpbrew';
    }

    static function getBuildDir()
    {
        return self::getPhpbrewRoot() . DIRECTORY_SEPARATOR . 'build';
    }

    static function getBuildPrefix()
    {
        return self::getPhpbrewRoot() . DIRECTORY_SEPARATOR . 'php';
    }

    static function getVersionBuildPrefix($version)
    {
        return self::getBuildPrefix() . DIRECTORY_SEPARATOR . $version;
    }

    static function getVersionEtcPath($version)
    {
        return self::getVersionBuildPrefix($version) . DIRECTORY_SEPARATOR . 'etc';
    }


    static function getVersionBinPath($version)
    {
        return self::getVersionBuildPrefix($version) . DIRECTORY_SEPARATOR . 'bin';
    }

    static function getInstalledPhpVersions()
    {
        $versions = array();
        $path = self::getPhpbrewRoot() . DIRECTORY_SEPARATOR . 'php';
        if( $fp = opendir( $path ) ) {
            while( ($item = readdir( $fp )) !== false ) {
                if( $item == '.' || $item == '..' )
                    continue;
                if( file_exists($path . DIRECTORY_SEPARATOR . $item . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'php' ) )
                    $versions[] = $item;
            }
            closedir( $fp );
        }
        return $versions;
    }

}

