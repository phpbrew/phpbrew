<?php
use PhpBrew\Testing\CommandTestCase;

/**
 * @large
 * @group command
 */
class KnownCommandTest extends CommandTestCase
{
    /**
     * @outputBuffering enabled
     */
    public function testCommand() {
        $this->assertTrue($this->runCommand('phpbrew known'));
    }

    /**
     * @outputBuffering enabled
     */
    public function testMoreOption() {
        $this->assertTrue($this->runCommand('phpbrew known --more'));
    }

    /**
     * @outputBuffering enabled
     */
    public function testOldMoreOption() {
        $this->assertTrue($this->runCommand('phpbrew known --old --more'));
    }


    /**
     * @outputBuffering enabled
     */
    public function testKnownUpdateCommand()
    {
        $this->assertTrue($this->runCommand('phpbrew known --update'));
        $this->assertTrue($this->runCommand('phpbrew known -u'));
    }
}
