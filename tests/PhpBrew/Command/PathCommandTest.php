<?php
use PhpBrew\Testing\CommandTestCase;

/**
 * @large
 * @group command
 */
class PathCommandTest extends CommandTestCase
{
    public function argumentsProvider() {

        return array( 
            array("build"),
            array("ext-src"),
            array("ext"),
            array("include"),
            array("etc"),
            array("dist"),
            array("root"),
            array("home"),
        );
    }

    /**
     * @outputBuffering enabled
     * @dataProvider argumentsProvider
     */
    public function testPathCommand($arg) {
        ob_start();
        $this->assertTrue($this->runCommand("phpbrew path $arg"));
        ob_end_clean();
    }
}
