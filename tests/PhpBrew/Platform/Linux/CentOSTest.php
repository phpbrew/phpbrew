<?php
namespace PhpBrew\Platform\Linux;

/**
 * @small
 */
class CentOSTest extends \PHPUnit_Framework_TestCase
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
