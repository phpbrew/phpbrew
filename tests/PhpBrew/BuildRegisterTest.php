<?php
use PhpBrew\Build;
use PhpBrew\BuildRegister;

/**
 * @small
 */
class BuildRegisterTest extends PHPUnit_Framework_TestCase
{
    public function testBuildRegister()
    {
        $this->markTestSkipped('Why is this failing on travis only? >.<');

        $b = new Build('5.4.19');

        $pool = new BuildRegister;
        $pool->register($b);
        $pool->deregister($b);
    }
}
