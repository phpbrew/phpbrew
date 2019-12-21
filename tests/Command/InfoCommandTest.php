<?php

namespace PHPBrew\Tests\Command;

use PHPBrew\Testing\CommandTestCase;

/**
 * @large
 * @group command
 */
class InfoCommandTest extends CommandTestCase
{
    /**
     * @outputBuffering enabled
     */
    public function testInfoCommand()
    {
        $this->assertCommandSuccess("phpbrew info");
    }
}
