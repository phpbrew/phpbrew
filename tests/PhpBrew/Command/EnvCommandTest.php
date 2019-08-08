<?php
use PhpBrew\Testing\CommandTestCase;

/**
 * @group command
 */
class EnvCommandTest extends CommandTestCase
{
    public function setupApplication()
    {
        return new PhpBrew\Console;
    }

    public function setUp() {
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

    /**
     * @outputBuffering enabled
     */
    public function testEnvCommandEmptyArg()
    {
        $this->assertCommandSuccess("phpbrew env ");
    }
}
