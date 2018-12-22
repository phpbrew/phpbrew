<?php
use PhpBrew\ReleaseList;

/**
 * @small
 */
class ReleaseListTest extends \PHPUnit\Framework\TestCase
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
            $versions['5.6.0'],
            array(
                'version' => "5.6.0",
                'announcement' => "https://php.net/releases/5_6_0.php",
                'date' => "28 Aug 2014",
                'filename' => "php-5.6.0.tar.bz2",
                'sha256' => "097af1be34fc73965e6f8401fd10e73eb56e1969ed4ffd691fb7e91606d0fc09",
                'name' => "PHP 5.6.0 (tar.bz2)",
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
