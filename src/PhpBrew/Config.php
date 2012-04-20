<?php
namespace PhpBrew;

class Config
{

    static function getPhpbrewRoot()
    {
        return getenv('PHPBREW_HOME');
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

    static function getVersionBuildLogPath($version)
    {
        return self::getVersionBuildPrefix($version) . DIRECTORY_SEPARATOR . 'build.log';
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

    static function getCurrentPhp()
    {
        return getenv('PHPBREW_PHP');
    }

    static function getCurrentPhpBin()
    {
        return getenv('PHPBREW_PATH');
    }
    

}

