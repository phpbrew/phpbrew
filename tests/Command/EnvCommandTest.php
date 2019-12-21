<?php

namespace PHPBrew\Tests\Command;

use PHPBrew\Console;
use PHPBrew\Testing\CommandTestCase;

/**
 * @group command
 */
class EnvCommandTest extends CommandTestCase
{
    public function setupApplication()
    {
        return new Console();
    }

    public function setUp()
    {
        parent::setUp();
        putenv('PHPBREW_HOME=' . getcwd() . '/.phpbrew');
        putenv('PHPBREW_ROOT=' . getcwd() . '/.phpbrew');
    }

    /**
     * @outputBuffering enabled
     */
    public function testEnvCommand()
    {
        $this->assertCommandSuccess("phpbrew env");
    }
}
