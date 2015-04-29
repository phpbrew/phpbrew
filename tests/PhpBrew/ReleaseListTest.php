<?php
use PhpBrew\ReleaseList;

/**
 * @small
 */
class ReleaseListTest extends PHPUnit_Framework_TestCase
{
    public $releaseList;

    public function setUp()
    {
        $this->releaseList = new ReleaseList;
        $this->releaseList->loadJsonFile('tests/fixtures/php-releases.json');
    }

    public function testGetVersions()
    {
        $versions = $this->releaseList->getVersions("5.3");
        $this->assertSame(
            $versions['5.3.0'],
            array(
                'version' => "5.3.0",
                'announcement' => "http://php.net/releases/5_3_0.php",
                'date' => "30 June 2009",
                'filename' => "php-5.3.0.tar.bz2",
                'md5' => "846760cd655c98dfd86d6d97c3d964b0",
                'name' => "PHP 5.3.0 (tar.bz2)",
            )
        );
    }

    public function versionDataProvider() {
        return array(
            array("5.3", "5.3.29"),
            array("5.4", "5.4.35"),
            array("5.5", "5.5.19"),
            array("5.6", "5.6.3"),
        );
    }

    /**
     * @dataProvider versionDataProvider
     */
    public function testLatestPatchVersion($major, $minor)
    {
        $version = $this->releaseList->getLatestPatchVersion($major, $minor);
        $this->assertInternalType('array', $version);
        $this->assertEquals($version['version'], $minor);
    }

    /**
     * @dataProvider versionDataProvider
     */
    public function testGetLatestVersion($major, $minor)
    {
        $latestVersion = $this->releaseList->getLatestVersion();

        $this->assertNotNull($latestVersion);

        $versions = $this->releaseList->getVersions($major);

        foreach ($versions as $versionInfo) {
            $this->assertTrue($latestVersion >= $versionInfo['version']);
        }
    }
}
