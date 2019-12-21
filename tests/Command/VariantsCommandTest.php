<?php

namespace PHPBrew\Tests\Command;

use PHPBrew\Testing\CommandTestCase;

/**
 * @large
 * @group command
 */
class VariantsCommandTest extends CommandTestCase
{
    /**
     * @outputBuffering enabled
     */
    public function testVariantsCommand()
    {
        $this->assertCommandSuccess("phpbrew variants");
    }
}
