<?php
use PhpBrew\Testing\CommandTestCase;

/**
 * @large
 * @group command
 */
class EnvCommandTest extends CommandTestCase
{
    public static function setupApplication()
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


}
