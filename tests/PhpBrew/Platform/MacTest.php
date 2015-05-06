<?php
namespace PhpBrew\Platform;

/**
 * @small
 */
class MacTest extends \PHPUnit_Framework_TestCase
{
    private $hardware;
    private $platform;

    public function setUp()
    {
        $this->hardware = new HardwareForMacTest();
        $this->platform = new Mac($this->hardware);
    }

    public function testIs32bitWhenHardwareIs32bit()
    {
        $this->hardware->is32bit = true;
        $this->assertTrue($this->platform->is32bit());
    }

    public function testIs64bit()
    {
        $this->hardware->is64bit = true;
        $this->assertTrue($this->platform->is64bit());
    }

    public function testIsMac()
    {
        $this->assertTrue($this->platform->isMac());
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
}

class HardwareForMacTest implements Hardware
{
    public $is32bit = false;
    public $is64bit = false;

    public function is32bit()
    {
        return $this->is32bit;
    }

    public function is64bit()
    {
        return $this->is64bit;
    }
}
