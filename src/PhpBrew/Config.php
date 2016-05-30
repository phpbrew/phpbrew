<?php

namespace PhpBrew;

use Exception;
use Symfony\Component\Yaml\Yaml;

/**
 * This config class provides settings based on the current environment
 * variables like PHPBREW_ROOT or PHPBREW_HOME.
 */
class Config
{
    protected static $currentPhpVersion = null;

    /**
     * Return optional home directory.
     *
     * @throws Exception
     *
     * @return string
     */
    public static function getHome()
    {
        if ($custom = getenv('PHPBREW_HOME')) {
            if (!file_exists($custom)) {
                mkdir($custom, 0755, true);
            }

            return $custom;
        }
        if ($home = getenv('HOME')) {
            $path = $home.DIRECTORY_SEPARATOR.'.phpbrew';
            if (!file_exists($path)) {
                mkdir($path, 0755, true);
            }

            return $path;
        }
        throw new Exception('Environment variable PHPBREW_HOME or HOME is required');
    }

    public static function setPhpbrewHome($home)
    {
        putenv('PHPBREW_HOME='.$home);
    }

    public static function setPhpbrewRoot($root)
    {
        putenv('PHPBREW_ROOT='.$root);
    }

    public static function getRoot()
    {
        if ($root = getenv('PHPBREW_ROOT')) {
            if (!file_exists($root)) {
                mkdir($root, 0755, true);
            }

            return $root;
        }
        if ($home = getenv('HOME')) {
            return $home.DIRECTORY_SEPARATOR.'.phpbrew';
        }
        throw new Exception('Environment variable PHPBREW_ROOT is required');
    }

    /**
     * Variants is private, so we use HOME path.
     */
    public static function getVariantsDir()
    {
        return self::getHome().DIRECTORY_SEPARATOR.'variants';
    }

    /**
     * cache directory for configure.
     */
    public static function getCacheDir()
    {
        return self::getRoot().DIRECTORY_SEPARATOR.'cache';
    }

    /**
     * php(s) could be global, so we use ROOT path.
     */
    public static function getBuildDir()
    {
        return self::getRoot().DIRECTORY_SEPARATOR.'build';
    }

    public static function getRegistryDir()
    {
        return self::getRoot().DIRECTORY_SEPARATOR.'registry';
    }

    public static function getCurrentBuildDir()
    {
        return self::getRoot().DIRECTORY_SEPARATOR.'build'.DIRECTORY_SEPARATOR.self::getCurrentPhpName();
    }

    public static function getDistFileDir()
    {
        $dir = self::getRoot().DIRECTORY_SEPARATOR.'distfiles';
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir;
    }

    public static function getTempFileDir()
    {
        $dir = self::getRoot().DIRECTORY_SEPARATOR.'tmp';
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir;
    }

    public static function getPHPReleaseListPath()
    {
        // Release list from php.net
        return self::getRoot().DIRECTORY_SEPARATOR.'php-releases.json';
    }

    /**
     * A build prefix is the prefix we specified when we install the PHP.
     *
     * when PHPBREW_ROOT is pointing to /home/user/.phpbrew
     *
     * php(s) will be installed into /home/user/.phpbrew/php/php-{version}
     *
     * @return string
     */
    public static function getInstallPrefix()
    {
        return self::getRoot().DIRECTORY_SEPARATOR.'php';
    }

    public static function getVersionInstallPrefix($version)
    {
        return self::getInstallPrefix().DIRECTORY_SEPARATOR.$version;
    }

    /**
     * XXX: This method should be migrated to PhpBrew\Build class.
     *
     * @param string $buildName
     *
     * @return string
     */
    public static function getVersionEtcPath($buildName)
    {
        return self::getVersionInstallPrefix($buildName).DIRECTORY_SEPARATOR.'etc';
    }

    public static function getVersionBinPath($buildName)
    {
        return self::getVersionInstallPrefix($buildName).DIRECTORY_SEPARATOR.'bin';
    }

    public static function putPathEnvFor($buildName)
    {
        $root = self::getRoot();
        $buildDir = $root.DIRECTORY_SEPARATOR.'php'.DIRECTORY_SEPARATOR.$buildName;

        // re-build path
        $paths = explode(PATH_SEPARATOR, getenv('PATH'));
        $paths = array_filter($paths, function ($p) use ($root) {
            return strpos($p, $root) === false;
        });
        array_unshift($paths, $buildDir.DIRECTORY_SEPARATOR.'bin');
        putenv('PATH='.implode(PATH_SEPARATOR, $paths));
    }

    /**
     * XXX: This method is now deprecated. use findMatchedBuilds insteads.
     *
     * @deprecated
     */
    public static function getInstalledPhpVersions()
    {
        $versions = array();
        $path = self::getRoot().DIRECTORY_SEPARATOR.'php';

        if (!file_exists($path)) {
            throw new Exception("$path doesn't exist.");
        }
        if ($fp = opendir($path)) {
            while (($item = readdir($fp)) !== false) {
                if ($item == '.' || $item == '..') {
                    continue;
                }
                if (file_exists($path.DIRECTORY_SEPARATOR.$item.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'php')) {
                    $versions[] = $item;
                }
            }
            closedir($fp);
        } else {
            throw new Exception('opendir failed');
        }
        rsort($versions);

        return $versions;
    }

    public static function getCurrentPhpConfigBin()
    {
        return self::getCurrentPhpDir().DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'php-config';
    }

    public static function getCurrentPhpizeBin()
    {
        return self::getCurrentPhpDir().DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'phpize';
    }

    /**
     * XXX: This method should be migrated to PhpBrew\Build class.
     */
    public static function getCurrentPhpConfigScanPath($home = false)
    {
        return self::getCurrentPhpDir($home).DIRECTORY_SEPARATOR.'var'.DIRECTORY_SEPARATOR.'db';
    }

    public static function getCurrentPhpDir($home = false)
    {
        if ($home) {
            return self::getHome().DIRECTORY_SEPARATOR.'php'.DIRECTORY_SEPARATOR.self::getCurrentPhpName();
        }

        return self::getRoot().DIRECTORY_SEPARATOR.'php'.DIRECTORY_SEPARATOR.self::getCurrentPhpName();
    }

    // XXX: needs to be removed.
    public static function useSystemPhpVersion()
    {
        self::$currentPhpVersion = null;
    }

    // XXX: needs to be removed.
    public static function setPhpVersion($phpVersion)
    {
        self::$currentPhpVersion = 'php-'.$phpVersion;
    }

    /**
     * getCurrentPhpName return the current php version from
     * self::$currentPhpVersion or from environment variable `PHPBREW_PHP`.
     *
     * @return string
     */
    public static function getCurrentPhpName()
    {
        if (self::$currentPhpVersion !== null) {
            return self::$currentPhpVersion;
        }

        return getenv('PHPBREW_PHP');
    }

    public static function getLookupPrefix()
    {
        return getenv('PHPBREW_LOOKUP_PREFIX');
    }

    public static function getCurrentPhpBin()
    {
        return getenv('PHPBREW_PATH');
    }

    public static function getConfig()
    {
        $configFile = self::getRoot().DIRECTORY_SEPARATOR.'config.yaml';
        if (!file_exists($configFile)) {
            return array();
        }

        return Yaml::parse(file_get_contents($configFile));
    }

    public static function getProxyConfig()
    {
        $configFile = self::getRoot().DIRECTORY_SEPARATOR.'proxy.yaml';
        if (!file_exists($configFile)) {
            return false;
        }

        return Yaml::parse(file_get_contents($configFile));
    }

    public static function getConfigParam($param = null)
    {
        $config = self::getConfig();
        if ($param && isset($config[$param])) {
            return $config[$param];
        }

        return $config;
    }

    public static function initDirectories($buildName = null)
    {
        $dirs = array();
        $dirs[] = self::getRoot();
        $dirs[] = self::getHome();
        $dirs[] = self::getVariantsDir();
        $dirs[] = self::getBuildDir();
        $dirs[] = self::getCacheDir();
        $dirs[] = self::getDistFileDir();
        $dirs[] = self::getRegistryDir();
        if ($buildName) {
            $dirs[] = self::getCurrentBuildDir($buildName);
            $dirs[] = self::getCurrentBuildDir($buildName).DIRECTORY_SEPARATOR.'ext';
            $dirs[] = self::getInstallPrefix($buildName).DIRECTORY_SEPARATOR.'var'.DIRECTORY_SEPARATOR.'db';
        }
        foreach ($dirs as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
        }

        $write = array();
        $write[] = self::getHome();
        $write[] = self::getVariantsDir();
        $write[] = self::getBuildDir();
        $write[] = self::getCacheDir();
        $write[] = self::getDistFileDir();
        $write[] = self::getRegistryDir();
        foreach ($write as $dir) {
            if (!is_writable($dir)) {
                throw new Exception("$dir is not writable, please fix the folder permissions.");
            }
        }
    }
}
