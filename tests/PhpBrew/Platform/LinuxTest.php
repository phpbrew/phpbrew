<?php

namespace PhpBrew\Tests\Platform;

use PhpBrew\Platform\Hardware;
use PhpBrew\Platform\Linux;
use PhpBrew\Platform\Linux\Distribution;
use PHPUnit\Framework\TestCase;

/**
 * @small
 */
class LinuxTest extends TestCase
{
    private $hardware;
    private $distribution;
    private $platform;

    public function setUp()
    {
        $this->hardware = new HardwareForLinuxTest();
        $this->distribution = new DistributionForLinuxTest();
        $this->platform = new LinuxForLinuxTest($this->hardware);
        $this->platform->setDistribution($this->distribution);
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
        $this->assertFalse($this->platform->isMac());
    }

    public function testIsLinux()
    {
        $this->assertTrue($this->platform->isLinux());
    }

    public function testIsDebian()
    {
        $this->assertFalse($this->platform->isDebian());
    }

    public function testIsCentOS()
    {
        $this->assertFalse($this->platform->isCentOS());
    }

    public function testGetOSMajorVersion()
    {
        $this->distribution->version = '5.3';
        $this->assertSame('5.3', $this->platform->getOSVersion());
    }
}

class LinuxForLinuxTest extends Linux
{
    public function setDistribution($distribution)
    {
        parent::setDistribution($distribution);
    }
}

class HardwareForLinuxTest implements Hardware
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

class DistributionForLinuxTest implements Distribution
{
    public $version = '';

    public function getVersion()
    {
        return $this->version;
    }

    public function isDebian()
    {
        return false;
    }

    public function isCentOS()
    {
        return false;
    }
}
