<?php
use PhpBrew\Testing\CommandTestCase;

class ExtensionCommandTest extends CommandTestCase
{
    public function extensionNameProvider() {
        return array(
            array('APCu', 'latest')
        );
    }

    /**
     * @outputBuffering enabled
     * @dataProvider extensionNameProvider
     */
    public function testExtInstallCommand($extensionName, $extensionVersion) {
        ob_start();
        $this->assertTrue($this->runCommand("phpbrew --quiet ext install $extensionName $extensionVersion"));
        ob_end_clean();
    }

    /**
     * @outputBuffering enabled
     * @dataProvider extensionNameProvider
     * @depends testExtInstallCommand
     */
    public function testExtShowCommand($extensionName, $extensionVersion) {
        ob_start();
        $this->assertTrue($this->runCommand("phpbrew --quiet ext show $extensionName"));
        ob_end_clean();
    }



    /**
     * @outputBuffering enabled
     * @dataProvider extensionNameProvider
     * @depends testExtInstallCommand
     */
    public function testExtCleanCommand($extensionName, $extensionVersion) {
        ob_start();
        $this->assertTrue($this->runCommand("phpbrew --quiet ext clean $extensionName"));
        ob_end_clean();
    }

    /**
     * @outputBuffering enabled
     * @depends testExtInstallCommand
     */
    public function testExtListCommand() {
        ob_start();
        $this->assertTrue($this->runCommand('phpbrew ext --show-path --show-options'));
        ob_end_clean();
    }


}
