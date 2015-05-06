<?php
namespace PhpBrew\Platform;

/**
 * @small
 */
class UnknownPlatformTest extends \PHPUnit_Framework_TestCase
{
    private $platform;

    public function setUp()
    {
        $this->platform = new UnknownPlatform();
    }

    public function testIs32bit()
    {
        $this->assertFalse($this->platform->is32bit());
    }

    public function testIs64bit()
    {
        $this->assertFalse($this->platform->is64bit());
    }

    public function testIsMac()
    {
        $this->assertFalse($this->platform->isMac());
    }

    public function testIsLinux()
    {
        $this->assertFalse($this->platform->isLinux());
    }

    public function testIsDebian()
    {
        $this->assertFalse($this->platform->isDebian());
    }

    public function testIsCentOS()
    {
        $this->assertFalse($this->platform->isCentOS());
    }

    public function testGetOSVersion()
    {
        $this->assertSame('', $this->platform->getOSVersion());
    }
}
