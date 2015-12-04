<?php
namespace PhpBrew;
use CurlKit\CurlDownloader;
use CurlKit\Progress\ProgressBar;
use GetOptionKit\OptionResult;
use Exception;
use RuntimeException;

defined('JSON_UNESCAPED_SLASHES') || define('JSON_UNESCAPED_SLASHES', 0);
defined('JSON_PRETTY_PRINT') || define('JSON_PRETTY_PRINT', 128);

class ReleaseList
{

    /**
     * $releases['5.3'] = [ {},... ]
     * $releases['5.4'] = [ {},... ]
     * $releases['5.5'] = [ {},... ]
     */
    public $releases = array();

    public $versions = array();

    public function __construct($releases = array())
    {
        $this->setReleases($releases);
    }

    public function setReleases(array $releases)
    {
        $this->releases = $releases;
        foreach ($this->releases as $major => $versionReleases) {
            foreach ($versionReleases as $version => $release) {
                $this->versions[ $version ] = $release;
            }
        }
    }

    public function getReleases()
    {
        return $this->releases;
    }

    public function loadJson($json)
    {
        if (!$json) {
            throw new RuntimeException("Can't load releases. Empty JSON given.");
        }
        if ($releases = json_decode($json, true)) {
            $this->setReleases($releases);

            return $releases;
        } else {
            throw new RuntimeException("Can't decode release json, invalid JSON string: " . substr($json,0, 125) );
        }
    }

    public function loadJsonFile($file)
    {
        $this->loadJson(file_get_contents($file));
    }

    /**
     * Returns the latest PHP version.
     */
    public function getLatestVersion()
    {
        $releases = $this->getReleases();
        $latestMajor = array_shift($releases);
        $latest = array_shift($latestMajor);
        if (!$latest) {
            throw new Exception("Latest major version not found.");
        }

        return $latest['version'];
    }

    public function getLatestPatchVersion($version)
    {
        if (isset($this->releases[$version])) {
            reset($this->releases[$version]);

            return current($this->releases[$version]);
        }
    }

    public function getVersion($version)
    {
        if (isset($this->releases[$version])) {
            return $this->getLatestPatchVersion($version);
        } elseif (isset($this->versions[$version])) {
            return $this->versions[$version];
        }

        return false;
    }

    /**
     * Get version by minor version number
     */
    public function getVersions($key)
    {
        if (isset($this->releases[$key])) {
            return $this->releases[$key];
        }
    }

    public function foundLocalReleaseList()
    {
        $releasesFile = Config::getPHPReleaseListPath();

        return file_exists($releasesFile);
    }

    public function loadLocalReleaseList()
    {
        if ($this->foundLocalReleaseList()) {
            $this->loadJsonFile(Config::getPHPReleaseListPath());

            return $this->releases;
        }
    }

    public function save()
    {
        $localFilepath = Config::getPHPReleaseListPath();
        $json = json_encode($this->releases, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (false === file_put_contents($localFilepath, $json)) {
            throw new Exception("Can't store release json file");
        }
    }

    public function fetchRemoteReleaseList(OptionResult $options = null)
    {
        $releases = self::buildReleaseListFromOfficialSite($options);
        $this->setReleases($releases);
        $this->save();
    }

    public static function getReadyInstance(OptionResult $options = null)
    {
        static $instance;

        if ($instance) { return $instance; }

        $instance = new self;

        if ($instance->foundLocalReleaseList()) {
            $instance->setReleases($instance->loadLocalReleaseList());
        } else {
            $instance->fetchRemoteReleaseList();
        }

        return $instance;
    }

    private static function downloadReleaseListFromOfficialSite($version, OptionResult $options = null)
    {
        if (!extension_loaded('openssl')) {
            throw new Exception(
                'openssl extension not found, to download releases file you need openssl.');
        }

        $max = ($options && $options->old) ? 1000 : 100;
        $url = "https://php.net/releases/index.php?json&version={$version}&max={$max}";

        if (extension_loaded('curl')) {
            $downloader = new CurlDownloader;
            $downloader->setProgressHandler(new ProgressBar);

            if (! Console::getInstance()->options->{'no-progress'}) {
                $downloader->setProgressHandler(new ProgressBar);
            }

            if ($options) {
                $seconds = $options->{'connect-timeout'};
                if ($seconds || $seconds = getenv('CONNECT_TIMEOUT')) {
                    $downloader->setConnectionTimeout($seconds);
                }
                if ($proxy = $options->{'http-proxy'}) {
                    $downloader->setProxy($proxy);
                }
                if ($proxyAuth = $options->{'http-proxy-auth'}) {
                    $downloader->setProxyAuth($proxyAuth);
                }
            }

            $json = $downloader->request($url);
        } else {
            $json = file_get_contents($url);
        }

        $obj = json_decode($json, true);
        return $obj;
    }

    public static function buildReleaseListFromOfficialSite(OptionResult $options = null)
    {
        $obj = array_merge(
            self::downloadReleaseListFromOfficialSite(7),
            self::downloadReleaseListFromOfficialSite(5)
        );
        $releaseVersions = array();
        foreach ($obj as $k => $v) {
            if (preg_match('/^(\d+)\.(\d+)\./', $k, $matches)) {
                list($o, $major, $minor) = $matches;
                $release = array( 'version' => $k );
                if (isset($v['announcement']['English'])) {
                    $release['announcement'] = 'http://php.net' . $v['announcement']['English'];
                }

                if (isset($v['date'])) {
                    $release['date'] = $v['date'];
                }
                foreach ($v['source'] as $source) {
                    if (isset($source['filename']) && preg_match('/\.tar\.bz2$/', $source['filename'])) {
                        $release['filename'] = $source['filename'];
                        $release['name']     = $source['name'];
                        if (isset($source['md5'])) {
                            $release['md5'] = $source['md5'];
                        }
                        if (isset($source['date'])) {
                            $release['date'] = $source['date'];
                        }
                    }
                }
                $releaseVersions["$major.$minor"][$k] = $release;
            }
        }

        foreach ($releaseVersions as $key => & $versions) {
            uksort($releaseVersions[$key],function ($a, $b) {
                return version_compare($b, $a);
            });
        }

        return $releaseVersions;
    }

}
