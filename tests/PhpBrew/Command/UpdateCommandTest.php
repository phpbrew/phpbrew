<?php
use PhpBrew\Testing\CommandTestCase;

class UpdateCommandTest extends CommandTestCase
{
    /**
     * @outputBuffering enabled
     */
    public function testUpdateCommand() {
        ob_start();
        $this->assertTrue($this->runCommand("phpbrew update"));
        ob_end_clean();
    }
}
