<?php
use PhpBrew\Utils;
use PhpBrew\Config;

class UtilsTest extends PHPUnit_Framework_TestCase
{
    public function test()
    {
        $this->assertInternalType('boolean', Utils::support64bit());
    }

    public function testLookupPrefix()
    {
        ok( Utils::getLookupPrefixes() );
    }

    public function testPrefix()
    {
        $this->assertNotNull(Utils::findLibPrefix('icu/pkgdata.inc','icu/Makefile.inc'));
        $this->assertNotNull(Utils::findIncludePrefix('openssl/opensslv.h'));
    }

    public function testFindbin()
    {
        ok(Utils::findBin('ls'));
        ok(Utils::findBin('psql'));
    }
}
