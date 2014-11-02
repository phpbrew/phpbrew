<?php
use CLIFramework\Testing\CommandTestCase;

class UpdateCommandTest extends CommandTestCase
{

    public function setupApplication() {
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
    public function testUpdateCommand() {
        ob_start();
        $this->assertTrue($this->runCommand("phpbrew update"));
        ob_end_clean();
    }


}
