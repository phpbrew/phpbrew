<?php
namespace PhpBrew;
use Exception;
use Symfony\Component\Yaml\Yaml;

class Config
{
    protected static $_currentPhpVersion = null;

    public static function getPhpbrewHome()
    {
        if ($custom = getenv('PHPBREW_HOME'))
            return $custom;

        if ( $home = getenv('HOME') ) {
            return $home . DIRECTORY_SEPARATOR . '.phpbrew';
        }
        throw new Exception('Environment variable PHPBREW_HOME or HOME is required');
    }

    public static function getPhpbrewRoot()
    {
        if ( $root = getenv('PHPBREW_ROOT')) {
            return $root;
        }
        if ( $home = getenv('HOME') ) {
            return $home . DIRECTORY_SEPARATOR . '.phpbrew';
        }
        throw new Exception('Environment variable PHPBREW_ROOT is required');
    }

    /**
     * Variants is private, so we use HOME path.
     */
    public static function getVariantsDir()
    {
        return self::getPhpbrewHome() . DIRECTORY_SEPARATOR . 'variants';
    }

    /**
     * php(s) could be global, so we use ROOT path.
     */
    public static function getBuildDir()
    {
        return self::getPhpbrewRoot() . DIRECTORY_SEPARATOR . 'build';
    }

    /**
     * A build prefix is the prefix we specified when we install the PHP.
     *
     * @return string
     */
    public static function getBuildPrefix()
    {
        return self::getPhpbrewRoot() . DIRECTORY_SEPARATOR . 'php';
    }

    public static function getVersionBuildPrefix($version)
    {
        return self::getBuildPrefix() . DIRECTORY_SEPARATOR . $version;
    }

    public static function getVersionBuildLogPath($version)
    {
        return self::getBuildDir() . DIRECTORY_SEPARATOR .  $version . DIRECTORY_SEPARATOR . 'build.log';
    }

    /**
     * XXX: This method should be migrated to PhpBrew\Build class.
     */
    public static function getVersionEtcPath($version)
    {
        return self::getVersionBuildPrefix($version) . DIRECTORY_SEPARATOR . 'etc';
    }

    public static function getVersionBinPath($version)
    {
        return self::getVersionBuildPrefix($version) . DIRECTORY_SEPARATOR . 'bin';
    }

    public static function getInstalledPhpVersions()
    {
        $versions = array();
        $path = self::getPhpbrewRoot() . DIRECTORY_SEPARATOR . 'php';
        if ( file_exists($path) && $fp = opendir( $path ) ) {
            while ( ($item = readdir( $fp )) !== false ) {
                if ($item == '.' || $item == '..') {
                    continue;
                }
                if ( file_exists($path . DIRECTORY_SEPARATOR . $item . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'php' ) ) {
                    $versions[] = $item;
                }
            }
            closedir( $fp );
        }

        return $versions;
    }

    /**
     * XXX: This method should be migrated to PhpBrew\Build class.
     */
    public static function getCurrentPhpConfigScanPath()
    {
        return self::getCurrentPhpDir() . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'db';
    }

    public static function getCurrentPhpDir()
    {
        return getenv('PHPBREW_ROOT') . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . self::getCurrentPhpName();
    }

    public static function useSystemPhpVersion()
    {
        self::$_currentPhpVersion = null;
    }

    public static function setPhpVersion($phpVersion)
    {
        self::$_currentPhpVersion = 'php-'.$phpVersion;
    }

    public static function getCurrentPhpName()
    {
        if (self::$_currentPhpVersion !== null) {
            return self::$_currentPhpVersion;
        }

        return getenv('PHPBREW_PHP');
    }

    public static function getCurrentPhpBin()
    {
        return getenv('PHPBREW_PATH');
    }

    public static function getConfigParam($param = null)
    {
        $configFile = self::getPhpbrewRoot() . DIRECTORY_SEPARATOR . 'config.yaml';
        $yaml = Yaml::parse($configFile);

        if (is_array($yaml)) {
            if ($param === null) {
                return $yaml;
            } elseif ($param != null && isset($yaml[$param])) {
                return $yaml[$param];
            }
        }

        return array();
    }
}
