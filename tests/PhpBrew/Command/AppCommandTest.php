<?php
use PhpBrew\Testing\CommandTestCase;

/**
 * @group command
 */
class AppCommandTest extends CommandTestCase
{
    /**
     * @outputBuffering enabled
     */
    public function testAppListCommand()
    {
        $this->assertCommandSuccess("phpbrew app list");
    }


    /**
     * @outputBuffering enabled
     */
    public function testAppGetCommand()
    {
        if (getenv('TRAVIS')) {
            return $this->markTestSkipped('skip app get command test on travis');
        }
        $this->assertCommandSuccess("phpbrew app get phpunit");
    }
}
