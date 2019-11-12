<?php

namespace PhpBrew\Tests\Command;

use PhpBrew\Testing\CommandTestCase;

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
