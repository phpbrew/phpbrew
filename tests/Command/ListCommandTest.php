<?php

namespace PHPBrew\Tests\Command;

use PHPBrew\Testing\CommandTestCase;

/**
 * @large
 * @group command
 */
class ListCommandTest extends CommandTestCase
{
    /**
     * @outputBuffering enabled
     */
    public function testListCommand()
    {
        $this->assertCommandSuccess("phpbrew list");
    }
}
