<?php
namespace PhpBrew\Platform\Linux;

/**
 * @small
 */
class DebianTest extends \\PHPUnit\Framework\TestCase
{
    private $distribution;

    public function setUp()
    {
        $this->distribution = new Debian();
    }

    public function testIsDebian()
    {
        $this->assertTrue($this->distribution->isDebian());
    }

    public function testIsCentOS()
    {
        $this->assertFalse($this->distribution->isCentOS());
    }
}
