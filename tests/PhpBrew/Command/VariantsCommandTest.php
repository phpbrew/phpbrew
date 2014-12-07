<?php
use PhpBrew\Testing\CommandTestCase;

/**
 * @large
 * @group command
 */
class VariantsCommandTest extends CommandTestCase
{
    /**
     * @outputBuffering enabled
     */
    public function testVariantsCommand() {
        ob_start();
        $this->assertTrue($this->runCommand("phpbrew variants"));
        ob_end_clean();
    }


}
