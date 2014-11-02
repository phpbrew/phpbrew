<?php
use CLIFramework\Testing\CommandTestCase;

class EnvCommandTest extends CommandTestCase
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
    public function testEnvCommand() {
        ob_start();
        $this->assertTrue($this->runCommand("phpbrew env"));
        ob_end_clean();
    }


}
