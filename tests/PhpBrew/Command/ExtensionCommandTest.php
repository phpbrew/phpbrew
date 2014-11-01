<?php
use CLIFramework\Testing\CommandTestCase;

class ExtensionCommandTest extends CommandTestCase
{

    public function setupApplication() {
        return new PhpBrew\Console;
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
            array('APCu')
        );
    }

    /**
     * @outputBuffering enabled
     * @dataProvider extensionNameProvider
     */
    public function testInstallCommand($extensionName) {
        ob_start();
        $this->assertTrue($this->runCommand("phpbrew -q ext install $extensionName"));
        ob_end_clean();
    }

}
