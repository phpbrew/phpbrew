<?php
use PhpBrew\Utils;
use PhpBrew\Config;

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

    public function testFindbin() {
        ok(Utils::findbin('ls'));
        ok(Utils::findbin('psql'));
    }

    public function testFindLatestPhpVersion() {
        $buildDir = Config::getBuildDir();

        $paths = array();
        $paths[] = $buildDir . DIRECTORY_SEPARATOR . 'php-12.3.4';
        $paths[] = $buildDir . DIRECTORY_SEPARATOR . 'php-12.3.6';

        // Create paths
        foreach ($paths as $path) {
            if (!is_dir($path)) {
                mkdir($path);
            }

        }

        is('12.3.6', Utils::findLatestPhpVersion('12'));
        is('12.3.6', Utils::findLatestPhpVersion('12.3'));
        is('12.3.4', Utils::findLatestPhpVersion('12.3.4'));
        is(false, Utils::findLatestPhpVersion('11'));

        // Cleanup paths
        foreach ($paths as $path) {
            if (is_dir($path)) {
                rmdir($path);
            }
        }
    }
}

