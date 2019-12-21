<?php

namespace PHPBrew\Tests\Command;

use PHPBrew\Testing\CommandTestCase;

/**
 * @large
 * @group command
 */
class InitCommandTest extends CommandTestCase
{
    /**
     * @outputBuffering enabled
     */
    public function testInitCommand()
    {
        ob_start();
        $this->assertTrue($this->runCommand("phpbrew init"));
        ob_end_clean();
    }
}
