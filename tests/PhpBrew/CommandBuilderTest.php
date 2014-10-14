<?php

class CommandBuilderTest extends PHPUnit_Framework_TestCase
{
    public function test()
    {
        ob_start();
        $cmd = new PhpBrew\CommandBuilder('ls');
        $this->assertEquals(0, $cmd->execute());
        ob_end_clean();
    }
}
