<?php
use PhpBrew\Utils;

class UtilsTest extends PHPUnit_Framework_TestCase
{
    public function test()
    {
        Utils::support_64bit();
        ok(1);
    }

    public function testPrefix() {
        ok( Utils::find_lib_prefix('icu/pkgdata.inc','icu/Makefile.inc','x86_64-linux-gnu/icu/pkgdata.inc') );
        ok( Utils::find_include_prefix('openssl/opensslv.h') );
    }

    public function testFindbin() {
        ok(Utils::findbin('apxs2'));
        ok(Utils::findbin('psql'));
    }
}

