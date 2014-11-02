<?php
use CLIFramework\Testing\CommandTestCase;

class ExtensionCommandTest extends CommandTestCase
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
    public function testListCommand() {
        ob_start();
        $this->assertTrue($this->runCommand('phpbrew ext'));
        ob_end_clean();
    }

    public function extensionNameProvider() {
        return array(
            array('APCu', 'latest')
        );
    }

    /**
     * @outputBuffering enabled
     * @dataProvider extensionNameProvider
     */
    public function testInstallCommand($extensionName, $extensionVersion) {
        ob_start();
        $this->assertTrue($this->runCommand("phpbrew --quiet ext install $extensionName $extensionVersion"));
        $this->assertTrue($this->runCommand("phpbrew --quiet ext show $extensionName"));
        $this->assertTrue($this->runCommand("phpbrew --quiet ext clean $extensionName"));
        ob_end_clean();
    }


}
