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
     * @group mayignore
     */
    public function testCommand() {
        $this->assertCommandSuccess('phpbrew known');
    }

    /**
     * @outputBuffering enabled
     * @group mayignore
     */
    public function testMoreOption() {
        $this->assertCommandSuccess('phpbrew known --more');
    }

    /**
     * @outputBuffering enabled
     */
    public function testOldMoreOption() {
        $this->assertCommandSuccess('phpbrew known --old --more');
    }


    /**
     * @outputBuffering enabled
     * @group mayignore
     */
    public function testKnownUpdateCommand()
    {
        $this->assertCommandSuccess('phpbrew known --update');
        $this->assertCommandSuccess('phpbrew known -u');
    }
}
