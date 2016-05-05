<?php
use PhpBrew\Testing\CommandTestCase;

/**
 * @large
 * @group command
 */
class InfoCommandTest extends CommandTestCase
{
    /**
     * @outputBuffering enabled
     */
    public function testInfoCommand() {
        $this->assertCommandSuccess("phpbrew info");
    }


}
