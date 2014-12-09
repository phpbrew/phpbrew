<?php
use PhpBrew\Utils;
use PhpBrew\Config;

/**
 * @small
 */
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

    public function testSystemReturnValueWhenSuccess()
    {
        $this->assertSame(0, Utils::system('true'));
    }

    /**
     * @expectedException \Exception
     */
    public function testSystemShouldThrowAnException()
    {
        Utils::system('false');
    }
}
