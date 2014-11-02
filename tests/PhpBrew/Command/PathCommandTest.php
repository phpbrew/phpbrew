<?php
use CLIFramework\Testing\CommandTestCase;

class PathCommandTest extends CommandTestCase
{

    public function setupApplication() {
        return new PhpBrew\Console;
    }

    public function setUp() {
        parent::setUp();
        putenv('PHPBREW_HOME=' . getcwd() . '/.phpbrew');
        putenv('PHPBREW_ROOT=' . getcwd() . '/.phpbrew');
    }

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
