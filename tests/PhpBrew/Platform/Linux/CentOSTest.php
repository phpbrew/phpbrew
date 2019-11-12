<?php

namespace PhpBrew\Tests\Platform\Linux;

use PhpBrew\Platform\Linux\CentOS;
use PHPUnit\Framework\TestCase;

/**
 * @small
 */
class CentOSTest extends TestCase
{
    private $distribution;

    public function setUp()
    {
        $this->distribution = new CentOS();
    }

    public function testIsDebian()
    {
        $this->assertFalse($this->distribution->isDebian());
    }

    public function testIsCentOS()
    {
        $this->assertTrue($this->distribution->isCentOS());
    }
}
