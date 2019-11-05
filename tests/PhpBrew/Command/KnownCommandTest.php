<?php

namespace PhpBrew\Tests\Command;

use PhpBrew\Testing\CommandTestCase;

/**
 * @large
 * @group command
 */
class KnownCommandTest extends CommandTestCase
{
    public $usesVCR = true;

    /**
     * @outputBuffering enabled
     * @group mayignore
     */
    public function testCommand()
    {
        $this->assertCommandSuccess('phpbrew --quiet known');
    }

    /**
     * @outputBuffering enabled
     * @group mayignore
     */
    public function testMoreOption()
    {
        $this->assertCommandSuccess('phpbrew --quiet known --more');
    }

    /**
     * @outputBuffering enabled
     */
    public function testOldMoreOption()
    {
        $this->assertCommandSuccess('phpbrew --quiet known --old --more');
    }


    /**
     * @outputBuffering enabled
     * @group mayignore
     */
    public function testKnownUpdateCommand()
    {
        $this->assertCommandSuccess('phpbrew --quiet known --update');
        $this->assertCommandSuccess('phpbrew --quiet known -u');
    }
}
