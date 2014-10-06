<?php
use PhpBrew\Build;
use PhpBrew\BuildRegister;

class BuildRegisterTest extends PHPUnit_Framework_TestCase
{
    public function testBuildRegister()
    {
        putenv('PHPBREW_ROOT=' . getcwd() . '/tests/.phpbrew');
        putenv('PHPBREW_HOME=' . getcwd() . '/tests/.phpbrew');

        $b = new Build('5.4.19');
        ok($b);

        $pool = new BuildRegister;
        ok($pool);

        $pool->register($b);
        $pool->deregister($b);
    }
}

