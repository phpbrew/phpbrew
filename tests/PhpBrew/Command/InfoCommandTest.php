<?php
use PhpBrew\Testing\CommandTestCase;

class InfoCommandTest extends CommandTestCase
{
    /**
     * @outputBuffering enabled
     */
    public function testInfoCommand() {
        ob_start();
        $this->assertTrue($this->runCommand("phpbrew info"));
        ob_end_clean();
    }


}
