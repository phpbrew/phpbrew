<?php
use PhpBrew\Testing\CommandTestCase;

class InitCommandTest extends CommandTestCase
{
    /**
     * @outputBuffering enabled
     */
    public function testInitCommand() {
        ob_start();
        $this->assertTrue($this->runCommand("phpbrew init"));
        ob_end_clean();
    }


}
