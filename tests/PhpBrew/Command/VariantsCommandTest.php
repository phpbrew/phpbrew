<?php
use CLIFramework\Testing\CommandTestCase;

class VariantsCommandTest extends CommandTestCase
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
    public function testVariantsCommand() {
        ob_start();
        $this->assertTrue($this->runCommand("phpbrew variants"));
        ob_end_clean();
    }


}
