<?php
use PhpBrew\Testing\CommandTestCase;

/**
 * @large
 * @group command
 */
class ListCommandTest extends CommandTestCase
{
    /**
     * @outputBuffering enabled
     */
    public function testListCommand() {
        $this->assertCommandSuccess("phpbrew list");
    }


}
