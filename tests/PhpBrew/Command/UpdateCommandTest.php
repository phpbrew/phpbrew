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
    public function testUpdateCommand()
    {
        $this->assertCommandSuccess("phpbrew --quiet update --old");
    }
}
