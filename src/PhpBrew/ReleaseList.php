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

    public function __construct($releases = array())
    { 
        $this->releases = $releases;
    }

    public function setReleases(array $releases)
    {
        $this->releases = $releases;
    }

    public function loadJson($json)
    {
        $this->releases = json_decode($json, true);
    }

    public function loadJsonFile($file) 
    {
        $this->loadJson(file_get_contents($file));
    }

    public function getLatestPatchVersion($major, $minor) {
        $key = "$major.$minor";
        return current($this->releases[$key]);
    }

    public function getVersions($major, $minor)
    {
        $key = "$major.$minor";
        if (isset($this->releases[$key])) {
            return $this->releases[$key];
        }
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
        return $this->releases = json_decode($json, true);
    }

}



