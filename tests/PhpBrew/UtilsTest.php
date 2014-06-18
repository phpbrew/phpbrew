<?php
use PhpBrew\Utils;

class UtilsTest extends PHPUnit_Framework_TestCase
{
    public function test()
    {
        $this->assertInternalType('boolean', Utils::support_64bit());
    }

    public function testLookupPrefix() {
        ok( Utils::get_lookup_prefixes() );
    }

    public function testPrefix() {
        $this->assertNotNull(Utils::find_lib_prefix('icu/pkgdata.inc','icu/Makefile.inc'));
        $this->assertNotNull(Utils::find_include_prefix('openssl/opensslv.h'));
    }

    public function testFindbin() {
        ok(Utils::findbin('ls'));
        ok(Utils::findbin('psql'));
    }
}

