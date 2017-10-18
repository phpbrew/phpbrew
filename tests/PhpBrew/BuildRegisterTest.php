<?php
use PhpBrew\Build;
use PhpBrew\BuildRegister;

/**
 * @small
 */
class BuildRegisterTest extends \PHPUnit\Framework\TestCase
{
    public function testBuildRegister()
    {
        $b = new Build('5.4.19');
        $pool = new BuildRegister;

        $this->assertTrue($pool->register($b));
        $this->assertTrue($pool->deregister($b));
    }
}
