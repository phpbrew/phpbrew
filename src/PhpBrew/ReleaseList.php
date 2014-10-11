<?php
namespace PhpBrew;

class ReleaseList
{
    public $releases = array();

    public function __construct()
    { 
    }

    public function setReleases(array $releases)
    {
        $this->releases = $releases;
    }

    static public function loadJson($json) {
        $obj = json_decode($json);
        $list = new self;
        foreach($obj as $k => $v) {
            if (preg_match('/^(\d+)\.(\d+)\./', $k, $matches)) {
                list($o, $major, $minor) = $matches;
                $list->releases[ "$major.$minor" ][$k] = $v;
            }
        }
        return $list;
    }

    public function getReleases($major, $minor) {
        $key = "$major.$minor";
        if (isset($this->releases[$key])) {
            return $this->releases[$key];
        }
    }


}



