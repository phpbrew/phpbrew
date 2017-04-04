<?php
namespace PhpBrew\Platform;

/**
 * @small
 */
class UnixLikeHardwareTest extends \PHPUnit\Framework\TestCase
{
    public function testBitnessWhenHardwareIs64Bit()
    {
        $hardware = new UnixLikeHardwareForTest(64);
        $this->assertFalse($hardware->is32bit());
        $this->assertTrue($hardware->is64bit());
    }

    public function testBitnessWhenHardwareIs32Bit()
    {
        $hardware = new UnixLikeHardwareForTest(32);
        $this->assertTrue($hardware->is32bit());
        $this->assertFalse($hardware->is64bit());
    }
}

class UnixLikeHardwareForTest extends UnixLikeHardware
{
    private $bitness;

    public function __construct($bitness)
    {
        $this->bitness = $bitness;
    }

    protected function detectBitness()
    {
        return $this->bitness;
    }
}
