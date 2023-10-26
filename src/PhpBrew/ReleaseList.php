<?php

namespace PhpBrew;

use CLIFramework\Logger;
use Exception;
use GetOptionKit\OptionResult;
use PhpBrew\Downloader\DownloadFactory;
use RuntimeException;

defined('JSON_UNESCAPED_SLASHES') || define('JSON_UNESCAPED_SLASHES', 0);
defined('JSON_PRETTY_PRINT') || define('JSON_PRETTY_PRINT', 128);

class ReleaseList
{
    /**
     * $releases['5.3'] = [ {},... ]
     * $releases['5.4'] = [ {},... ]
     * $releases['5.5'] = [ {},... ].
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
            throw new RuntimeException("Can't decode release json, invalid JSON string: " . substr($json, 0, 125));
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
            throw new Exception('Latest major version not found.');
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
     * Get version by minor version number.
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

    public function fetchRemoteReleaseList(OptionResult $options)
    {
        $releases = self::buildReleaseListFromOfficialSite($options);
        $this->setReleases($releases);
        $this->save();
    }

    public static function getReadyInstance(OptionResult $options = null)
    {
        static $instance;

        if ($instance) {
            return $instance;
        }

        $instance = new self();

        if ($instance->foundLocalReleaseList()) {
            $instance->setReleases($instance->loadLocalReleaseList());
        } else {
            $instance->fetchRemoteReleaseList($options);
        }

        return $instance;
    }

    private static function downloadReleaseListFromOfficialSite($version, $max, OptionResult $options)
    {
        $url = 'https://www.php.net/releases/index.php?' . http_build_query(array(
            'json'    => true,
            'version' => $version,
            'max'     => $max,
        ));

        $file = DownloadFactory::getInstance(Logger::getInstance(), $options)->download($url);
        $json = file_get_contents($file);

        return json_decode($json, true) ?? [];
    }

    private static function buildReleaseListFromOfficialSite(OptionResult $options)
    {
        $obj = array_merge(
            self::downloadReleaseListFromOfficialSite(8, 100, $options),
            self::downloadReleaseListFromOfficialSite(7, 100, $options)
        );

        if ($options->get('old')) {
            $obj = array_merge($obj, self::downloadReleaseListFromOfficialSite(5, 1000, $options));
        }

        $releaseVersions = array();
        foreach ($obj as $k => $v) {
            if (preg_match('/^(\d+)\.(\d+)\./', $k, $matches)) {
                list(, $major, $minor) = $matches;
                $release = array('version' => $k);
                if (isset($v['announcement']['English'])) {
                    $release['announcement'] = 'https://php.net' . $v['announcement']['English'];
                }

                if (isset($v['date'])) {
                    $release['date'] = $v['date'];
                }
                foreach ($v['source'] as $source) {
                    if (isset($source['filename']) && preg_match('/\.tar\.bz2$/', $source['filename'])) {
                        $release['filename'] = $source['filename'];
                        $release['name'] = $source['name'];
                        if (isset($source['md5'])) {
                            $release['md5'] = $source['md5'];
                        }
                        if (isset($source['sha256'])) {
                            $release['sha256'] = $source['sha256'];
                        }
                        if (isset($source['date'])) {
                            $release['date'] = $source['date'];
                        }
                    }
                }
                $release['museum'] = isset($v['museum']) && $v['museum'];
                $releaseVersions["$major.$minor"][$k] = $release;
            }
        }

        foreach ($releaseVersions as $key => $_) {
            uksort($releaseVersions[$key], function ($a, $b) {
                return version_compare($b, $a);
            });
        }

        return $releaseVersions;
    }
}
