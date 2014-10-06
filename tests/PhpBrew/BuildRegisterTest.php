<?php
use PhpBrew\Build;
use PhpBrew\BuildRegister;

class BuildRegisterTest extends PHPUnit_Framework_TestCase
{
    public function testBuildRegister()
    {
        $b = new Build('5.4.19');
        ok($b);

        $pool = new BuildRegister;
        ok($pool);

        $pool->register($b);
        $pool->deregister($b);
    }
}

