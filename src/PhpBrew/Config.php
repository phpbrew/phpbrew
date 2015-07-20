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

    public static function getPhpbrewHome()
    {
        if ($custom = getenv('PHPBREW_HOME')) {
            if (!file_exists($custom)) {
                mkdir($custom, 0755, true);
            }
            return $custom;
        }
        if ($home = getenv('HOME')) {
            return $home . DIRECTORY_SEPARATOR . '.phpbrew';
        }
        throw new Exception('Environment variable PHPBREW_HOME or HOME is required');
    }

    public static function setPhpbrewHome($home)
    {
        putenv('PHPBREW_HOME='.  $home);
    }

    public static function setPhpbrewRoot($root)
    {
        putenv('PHPBREW_ROOT='.  $root);
    }

    public static function getPhpbrewRoot()
    {
        if ($root = getenv('PHPBREW_ROOT')) {
            if (!file_exists($root)) {
                mkdir($root, 0755, true);
            }
            return $root;
        }
        if ($home = getenv('HOME')) {
            return $home . DIRECTORY_SEPARATOR . '.phpbrew';
        }
        throw new Exception('Environment variable PHPBREW_ROOT is required');
    }

    /**
     * Variants is private, so we use HOME path.
     */
    static public function getVariantsDir()
    {
        return self::getPhpbrewHome() . DIRECTORY_SEPARATOR . 'variants';
    }

    /**
     * php(s) could be global, so we use ROOT path.
     */
    static public function getBuildDir()
    {
        return self::getPhpbrewRoot() . DIRECTORY_SEPARATOR . 'build';
    }


    static public function getCurrentBuildDir() {
        return self::getPhpbrewRoot() . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . self::getCurrentPhpName();
    }

    static public function getDistFileDir()
    {
        $dir =  self::getPhpbrewRoot() . DIRECTORY_SEPARATOR . 'distfiles';
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }

    static public function getTempFileDir()
    {
        $dir =  self::getPhpbrewRoot() . DIRECTORY_SEPARATOR . 'tmp';
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }

    static public function getPHPReleaseListPath()
    {
        // Release list from php.net
        return self::getPhpbrewRoot() . DIRECTORY_SEPARATOR . 'php-releases.json';
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
    static public function getInstallPrefix()
    {
        return self::getPhpbrewRoot() . DIRECTORY_SEPARATOR . 'php';
    }


    static public function getVersionInstallPrefix($version)
    {
        return self::getInstallPrefix() . DIRECTORY_SEPARATOR . $version;
    }

    /**
     * XXX: This method should be migrated to PhpBrew\Build class.
     *
     * @param string $buildName
     *
     * @return string
     */
    static public function getVersionEtcPath($buildName)
    {
        return self::getVersionInstallPrefix($buildName) . DIRECTORY_SEPARATOR . 'etc';
    }

    static public function getVersionBinPath($buildName)
    {
        return self::getVersionInstallPrefix($buildName) . DIRECTORY_SEPARATOR . 'bin';
    }

    static public function findInstalledBuilds($stripPrefix = true)
    {
        $path = self::getPhpbrewRoot() . DIRECTORY_SEPARATOR . 'php';
        if (!file_exists($path)) {
            throw new Exception($path . ' does not exist.');
        }
        $names = scandir($path);
        $names = array_filter($names, function($name) use ($path) {
            return $name != '.' && $name != '..' && file_exists($path . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'php');
        });

        if ($names == null || empty($names)) {
            return array();
        }

        if ($stripPrefix) {
            $names = array_map(function($name)  { return preg_replace('/^php-(?=(\d+\.\d+\.\d+)$)/','', $name); }, $names);
        }
        uasort($names, 'version_compare'); // ordering version name ascending... 5.5.17, 5.5.12
        return array_reverse($names);  // make it descending... since there is no sort function for user-define in reverse order.
    }

    static public function findMatchedBuilds($buildNameRE = '', $stripPrefix = true)
    {
        $builds = self::findInstalledBuilds($stripPrefix);
        return array_filter($builds, function($build) use ($buildNameRE) {
            return preg_match("/^$buildNameRE/i", $build);
        });
    }

    static public function findFirstMatchedBuild($buildNameRE = '', $stripPrefix = true)
    {
        $builds = self::findInstalledBuilds($stripPrefix);
        foreach ($builds as $build) {
            if (preg_match("/$buildNameRE/i", $build)) {
                return $build;
            }
        }
    }

    static public function findLatestBuild($stripPrefix = true) {
        $builds = Config::findInstalledBuilds($stripPrefix);
        if (!empty($builds)) {
            return $builds[0]; // latest
        }
    }

    static public function putPathEnvFor($buildName) {
        $root = Config::getPhpbrewRoot();
        $buildDir = $root . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . $buildName;

        // re-build path
        $paths = explode(':',getenv('PATH'));
        $paths = array_filter($paths, function($p) use ($root) {
            return (strpos($p, $root) === False);
        });
        array_unshift($paths, $buildDir . DIRECTORY_SEPARATOR . 'bin');
        putenv('PATH=' . join(':', $paths));
    }


    /**
     * XXX: This method is now deprecated. use findMatchedBuilds insteads.
     */
    static public function getInstalledPhpVersions()
    {
        $versions = array();
        $path = self::getPhpbrewRoot() . DIRECTORY_SEPARATOR . 'php';

        if (file_exists($path) && $fp = opendir($path)) {
            while (($item = readdir($fp)) !== false) {
                if ($item == '.' || $item == '..') {
                    continue;
                }

                if (file_exists($path . DIRECTORY_SEPARATOR . $item . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'php')) {
                    $versions[] = $item;
                }
            }

            closedir($fp);
        }

        rsort($versions);

        return $versions;
    }

    static public function getCurrentPhpConfigBin()
    {
        return self::getCurrentPhpDir() . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'php-config';
    }

    static public function getCurrentPhpizeBin() 
    {
        return self::getCurrentPhpDir() . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'phpize';
    }

    /**
     * XXX: This method should be migrated to PhpBrew\Build class.
     */
    static public function getCurrentPhpConfigScanPath($home = false)
    {
        return self::getCurrentPhpDir($home) . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'db';
    }

    static public function getCurrentPhpDir($home = false)
    {
        if ($home) {
            return self::getPhpbrewHome() . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . self::getCurrentPhpName();
        }
        return self::getPhpbrewRoot() . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . self::getCurrentPhpName();
    }



    // XXX: needs to be removed.
    static public function useSystemPhpVersion()
    {
        self::$currentPhpVersion = null;
    }

    // XXX: needs to be removed.
    static public function setPhpVersion($phpVersion)
    {
        self::$currentPhpVersion = 'php-'.$phpVersion;
    }


    /**
     * getCurrentPhpName return the current php version from
     * self::$currentPhpVersion or from environment variable `PHPBREW_PHP`
     *
     * @return string
     */
    static public function getCurrentPhpName()
    {
        if (self::$currentPhpVersion !== null) {
            return self::$currentPhpVersion;
        }
        return getenv('PHPBREW_PHP');
    }

    static public function getLookupPrefix()
    {
        return getenv('PHPBREW_LOOKUP_PREFIX');
    }

    static public function getCurrentPhpBin()
    {
        return getenv('PHPBREW_PATH');
    }

    static public function getConfigParam($param = null)
    {
        $configFile = self::getPhpbrewRoot() . DIRECTORY_SEPARATOR . 'config.yaml';

        if (!file_exists($configFile)) {
            return array();
        }

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

    static public function initDirectories($buildName = NULL) {
        $dirs[] = self::getPhpbrewHome();
        $dirs[] = self::getPhpbrewRoot();
        $dirs[] = self::getVariantsDir();
        $dirs[] = self::getBuildDir();
        $dirs[] = self::getDistFileDir();
        if ($buildName) {
            $dirs[] = self::getCurrentBuildDir($buildName);
            $dirs[] = self::getCurrentBuildDir($buildName) . DIRECTORY_SEPARATOR . 'ext';
            $dirs[] = self::getInstallPrefix($buildName) . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'db';
        }
        foreach($dirs as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

}
