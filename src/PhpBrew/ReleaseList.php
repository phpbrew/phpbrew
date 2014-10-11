<?php
namespace PhpBrew;

class ReleaseList
{

    /**
     * $releases['5.3'] = [ {},... ]
     * $releases['5.4'] = [ {},... ]
     * $releases['5.5'] = [ {},... ]
     */
    public $releases = array();

    public function __construct()
    { 
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

    public function getVersions($major, $minor)
    {
        $key = "$major.$minor";
        if (isset($this->releases[$key])) {
            return $this->releases[$key];
        }
    }

}



