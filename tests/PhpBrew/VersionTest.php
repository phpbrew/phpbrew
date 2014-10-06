<?php
use PhpBrew\Version;

class VersionTest extends PHPUnit_Framework_TestCase
{
    public function testVersionConstructor()
    {
        $versionA = new Version('php-5.4.22');
        ok($versionA);

        $versionB = new Version('5.4.22', 'php');
        ok($versionB);

        is('php-5.4.22',$versionA->getCanonicalizedVersionName());
        is('php-5.4.22',$versionB->getCanonicalizedVersionName());

        is('5.4.22',$versionA->getVersion());
        is('5.4.22',$versionB->getVersion());

        is($versionA->__toString(), $versionB->__toString());
    }

    public function testFindLastPatchVersion() {
        $ret = Version::findLatestPatchVersion('5.5', array('5.5.26', '5.5.25', '5.5.1'));
        is('5.5.26',$ret);
    }
}

