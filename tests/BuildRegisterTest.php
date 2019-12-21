<?php

namespace PHPBrew\Tests;

use PHPBrew\Build;
use PHPBrew\BuildRegister;
use PHPUnit\Framework\TestCase;

/**
 * @small
 */
class BuildRegisterTest extends TestCase
{
    public function testBuildRegister()
    {
        $b = new Build('5.4.19');
        $pool = new BuildRegister();

        $this->assertTrue($pool->register($b));
        $this->assertTrue($pool->deregister($b));
    }
}
