<?php
use PhpBrew\ReleaseList;

class ReleaseListTest extends PHPUnit_Framework_TestCase
{
    public $releaseList;

    public function setUp()
    {
        $this->releaseList = new ReleaseList;
        $this->releaseList->loadJsonFile('assets/releases.json');
    }

    public function testGetVersions()
    {
        $versions = $this->releaseList->getVersions(5,3);
        ok($versions);
        ok(is_array($versions));
    }


    public function versionDataProvider() {
        return array(
            array(5,3),
            array(5,4),
            array(5,5),
            array(5,6),
        );
    }

    /**
     * @dataProvider versionDataProvider
     */
    public function testLatestPatchVersion($major, $minor)
    {
        $version = $this->releaseList->getLatestPatchVersion($major, $minor);
        ok($version);
        ok($version['filename']);
        ok($version['md5']);
    }

}

