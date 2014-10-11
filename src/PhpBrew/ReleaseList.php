<?php
namespace PhpBrew;
use CurlKit\CurlDownloader;
use CurlKit\Progress\ProgressBar;
use PhpBrew\Config;

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
        $this->releases = $releases;
    }

    public function setReleases(array $releases)
    {
        $this->releases = $releases;
        foreach($this->releases as $major => $versionReleases) {
            foreach($versionReleases as $version => $release) {
                $this->versions[ $version ] = $release;
            }
        }
    }

    public function loadJson($json)
    {
        $releases = json_decode($json, true);
        $this->setReleases($releases);
        return $releases;
    }

    public function loadJsonFile($file) 
    {
        $this->loadJson(file_get_contents($file));
    }

    public function getLatestPatchVersion($version) {
        if (isset($this->releases[$version])) {
            reset($this->releases[$version]);
            return current($this->releases[$version]);
        }
    }

    public function getVersion($fullQualifiedVersion) {
        if (isset($this->versions[$fullQualifiedVersion])) {
            return $this->versions[$fullQualifiedVersion];
        }
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

    public function loadLocalReleaseList() {
        $releaseListFile = Config::getPHPReleaseListPath();
        if ($json = file_get_contents($releaseListFile)) {
            return $this->loadJson($json);
        }
        return false;
    }

    public function getRemoteReleaseListUrl($branch)
    {
        return "https://raw.githubusercontent.com/phpbrew/phpbrew/$branch/assets/releases.json";
    }

    public function fetchRemoteReleaseList($branch = 'master') {
        $downloader = new CurlDownloader;
        $downloader->setProgressHandler(new ProgressBar);
        $url = $this->getRemoteReleaseListUrl($branch);
        $json = $downloader->request($url);
        $localFilepath = Config::getPHPReleaseListPath();
        if (false === file_put_contents($localFilepath, $json)) {
            throw new Exception("Can't store release json file");
        }
        return $this->loadJson($json);
    }


    public function foundLocalReleaseList() {
        $releaseListFile = Config::getPHPReleaseListPath();
        return file_exists($releaseListFile);
    }
}



