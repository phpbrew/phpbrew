<?php
use PhpBrew\Testing\CommandTestCase;

/**
 * @large
 * @group command
 */
class UpdateCommandTest extends CommandTestCase
{
    /**
     * @outputBuffering enabled
     */
    public function testUpdateCommand() {
        ob_start();
        $this->assertTrue($this->runCommand("phpbrew --quiet update"));
        ob_end_clean();
    }
}
