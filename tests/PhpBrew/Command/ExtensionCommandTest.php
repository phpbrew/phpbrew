<?php

namespace PhpBrew\Tests\Command;

use PhpBrew\Testing\CommandTestCase;

/**
 * @large
 * @group command
 */
class ExtensionCommandTest extends CommandTestCase
{
    public function extensionNameProvider()
    {
        return array(
            array('APCu', 'latest'),
            array('xdebug', 'latest'),
        );
    }

    /**
     * @outputBuffering enabled
     * @dataProvider extensionNameProvider
     */
    public function testExtInstallCommand($extensionName, $extensionVersion)
    {
        $this->markTestSkipped("This test can not be run against system php");
        $this->assertTrue($this->runCommandWithStdout("phpbrew ext install $extensionName $extensionVersion"));
    }

    /**
     * @outputBuffering enabled
     * @dataProvider extensionNameProvider
     * @depends testExtInstallCommand
     */
    public function testExtShowCommand($extensionName, $extensionVersion)
    {
        $this->assertTrue($this->runCommandWithStdout("phpbrew ext show $extensionName"));
    }



    /**
     * @outputBuffering enabled
     * @dataProvider extensionNameProvider
     * @depends testExtInstallCommand
     */
    public function testExtCleanCommand($extensionName, $extensionVersion)
    {
        $this->assertTrue($this->runCommandWithStdout("phpbrew ext clean $extensionName"));
    }

    /**
     * @outputBuffering enabled
     * @depends testExtInstallCommand
     */
    public function testExtListCommand()
    {
        $this->assertTrue($this->runCommandWithStdout('phpbrew ext --show-path --show-options'));
    }
}
