<?php

class BuildTest extends PHPUnit_Framework_TestCase
{
    public function test()
    {
        $build = new PhpBrew\Build;
        $build->setVersion('5.2.0');
        
    }
}

