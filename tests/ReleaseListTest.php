<?php

namespace PHPBrew\Tests;

use PHPBrew\ReleaseList;
use PHPUnit\Framework\TestCase;

/**
 * @small
 */
class ReleaseListTest extends TestCase
{
    public $releaseList;

    public function setUp()
    {
        $this->releaseList = new ReleaseList();
        $this->releaseList->loadJsonFile(__DIR__ . '/fixtures/php-releases.json');
    }

    public function testGetVersions()
    {
        $versions = $this->releaseList->getVersions("7.2");
        $this->assertSame(
            $versions['7.2.0'],
            array(
                'version' => "7.2.0",
                'announcement' => "https://php.net/releases/7_2_0.php",
                'date' => "30 Nov 2017",
                'filename' => "php-7.2.0.tar.bz2",
                'name' => "PHP 7.2.0 (tar.bz2)",
                'sha256' => "2bfefae4226b9b97879c9d33078e50bdb5c17f45ff6e255951062a529720c64a",
                'museum' => false
            )
        );
    }

    public function versionDataProvider()
    {
        return array(
            array("7.3", "7.3.0"),
            array("7.2", "7.2.13"),
            array("5.4", "5.4.45"),
            array("5.6", "5.6.39"),
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
