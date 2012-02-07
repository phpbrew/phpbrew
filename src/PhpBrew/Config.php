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

    static function getVersionBuildPrefix($version)
    {
        return self::getPhpbrewRoot() . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . $version;
    }


    static function getVersionBinPath($version)
    {
        return self::getVersionBuildPrefix($version) . DIRECTORY_SEPARATOR . 'bin';
    }

}




