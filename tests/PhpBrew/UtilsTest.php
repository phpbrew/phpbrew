<?php
use PhpBrew\Utils;

class UtilsTest extends PHPUnit_Framework_TestCase
{
    public function test()
    {
        Utils::support_64bit();
        ok(1);
    }

    public function testLookupPrefix() {
        ok( Utils::get_lookup_prefixes() );
    }

    public function testPrefix() {
        ok( Utils::find_lib_prefix('icu/pkgdata.inc','icu/Makefile.inc') );
        ok( Utils::find_include_prefix('openssl/opensslv.h') );
    }
}

