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
        putenv('PHPBREW_ROOT=' . getcwd() . '/tests/.phpbrew');
        putenv('PHPBREW_HOME=' . getcwd() . '/tests/.phpbrew');

        $b = new Build('5.4.19');

        $pool = new BuildRegister;
        $pool->register($b);
        $pool->deregister($b);
    }
}
