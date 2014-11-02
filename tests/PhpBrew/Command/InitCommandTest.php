<?php
use CLIFramework\Testing\CommandTestCase;

class InitCommandTest extends CommandTestCase
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
    public function testInitCommand() {
        ob_start();
        $this->assertTrue($this->runCommand("phpbrew init"));
        ob_end_clean();
    }


}
