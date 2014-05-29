<?php
namespace PhpBrew;
use Exception;
use Symfony\Component\Yaml\Yaml;

class Config
{
    static protected $_currentPhpVersion = null;

    static function getPhpbrewHome()
    {
        if ($custom = getenv('PHPBREW_HOME')) 
            return $custom;

        if( $home = getenv('HOME') ) {
            return $home . DIRECTORY_SEPARATOR . '.phpbrew';
        }
        throw new Exception('Environment variable PHPBREW_HOME or HOME is required');
    }


    static function getPhpbrewRoot()
    {
        if( $root = getenv('PHPBREW_ROOT')) {
            return $root;
        }
        if( $home = getenv('HOME') ) {
            return $home . DIRECTORY_SEPARATOR . '.phpbrew';
        }
        throw new Exception('Environment variable PHPBREW_ROOT is required');
    }



    /**
     * Variants is private, so we use HOME path.
     */
    static function getVariantsDir()
    {
        return self::getPhpbrewHome() . DIRECTORY_SEPARATOR . 'variants';
    }


    /**
     * php(s) could be global, so we use ROOT path.
     */
    static function getBuildDir()
    {
        return self::getPhpbrewRoot() . DIRECTORY_SEPARATOR . 'build';
    }


    /**
     * A build prefix is the prefix we specified when we install the PHP.
     *
     * @return string
     */
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
        return self::getBuildDir() . DIRECTORY_SEPARATOR .  $version . DIRECTORY_SEPARATOR . 'build.log';
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
        if( file_exists($path) && $fp = opendir( $path ) ) {
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

    static function getCurrentPhpConfigScanPath()
    {
        return self::getCurrentPhpDir() . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'db';
    }

    static function getCurrentPhpDir()
    {
        return getenv('PHPBREW_ROOT') . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . self::getCurrentPhpName();
    }

    static function useSystemPhpVersion()
    {
        self::$_currentPhpVersion = null;
    }

    static function setPhpVersion($phpVersion)
    {
        self::$_currentPhpVersion = 'php-'.$phpVersion;
    }

    static function getCurrentPhpName()
    {
        if (self::$_currentPhpVersion !== null) {
            return self::$_currentPhpVersion;
        }

        return getenv('PHPBREW_PHP');
    }

    static function getCurrentPhpBin()
    {
        return getenv('PHPBREW_PATH');
    }

    static function getConfigParam($param = null)
    {
        $configFile = self::getPhpbrewRoot() . DIRECTORY_SEPARATOR . 'config.yaml';
        $yaml = Yaml::parse($configFile);

        if ($param === null) {
            return $yaml;
        } elseif ($param != null && isset($yaml[$param])) {
            return $yaml[$param];
        }

        return array();
    }
}

