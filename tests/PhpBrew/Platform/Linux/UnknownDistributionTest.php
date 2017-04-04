<?php
namespace PhpBrew\Platform\Linux;

/**
 * @small
 */
class UnknownDistributionTest extends \PHPUnit\Framework\TestCase
{
    private $distribution;

    public function setUp()
    {
        $this->distribution = new UnknownDistribution();
    }

    public function testGetVersion()
    {
        $this->assertSame('', $this->distribution->getVersion());
    }

    public function testIsDebian()
    {
        $this->assertFalse($this->distribution->isDebian());
    }

    public function testIsCentOS()
    {
        $this->assertFalse($this->distribution->isCentOS());
    }
}

