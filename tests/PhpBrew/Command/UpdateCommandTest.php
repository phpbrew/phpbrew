<?php
use PhpBrew\Testing\CommandTestCase;

/**
 * @large
 * @group command
 */
class UpdateCommandTest extends CommandTestCase
{
    public $usesVCR = true;

    /**
     * @outputBuffering enabled
     */
    public function testUpdateCommand()
    {
        $this->assertCommandSuccess("phpbrew --quiet update --old");
    }
}
