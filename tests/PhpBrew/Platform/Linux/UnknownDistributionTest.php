<?php

namespace PhpBrew\Tests\Platform\Linux;

use PhpBrew\Platform\Linux\UnknownDistribution;
use PHPUnit\Framework\TestCase;

/**
 * @small
 */
class UnknownDistributionTest extends TestCase
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
