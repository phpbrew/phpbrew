<?php
use PhpBrew\Version;

/**
 * @small
 */
class VersionTest extends \PHPUnit\Framework\TestCase
{
    public function testVersionConstructor()
    {
        $versionA = new Version('php-5.4.22');
        $versionB = new Version('5.4.22', 'php');

        $this->assertEquals('php-5.4.22',$versionA->getCanonicalizedVersionName());
        $this->assertEquals('php-5.4.22',$versionB->getCanonicalizedVersionName());
        $this->assertEquals('5.4.22',$versionA->getVersion());
        $this->assertEquals('5.4.22',$versionB->getVersion());
        $this->assertEquals($versionA->__toString(), $versionB->__toString());
    }

    public function testFindLastPatchVersion() {
        $ret = Version::findLatestPatchVersion('5.5', array('5.5.26', '5.5.25', '5.5.1'));
        $this->assertEquals('5.5.26',$ret);
    }

    public function testHasPatchVersion() {
        $this->assertTrue(Version::hasPatchVersion('5.5.2'));
        $this->assertFalse(Version::hasPatchVersion('5.5'));
    }

    public function testGetPatchVersion() {
        $version = new Version('php-5.4.22');
        $this->assertSame(22, $version->getPatchVersion());
    }
}
