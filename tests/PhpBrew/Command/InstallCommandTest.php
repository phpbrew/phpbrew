<?php
use PhpBrew\Testing\CommandTestCase;
use PhpBrew\Machine;

/**
 * @large
 * @group command
 */
class InstallCommandTest extends CommandTestCase
{
    /**
     * @outputBuffering enabled
     */
    public function testKnownCommand()
    {
        $this->assertTrue($this->runCommand("phpbrew init"));
        $this->assertTrue($this->runCommand("phpbrew known --update"));
    }

    /**
     * @outputBuffering enabled
     * @depends testKnownCommand
     */
    public function testInstallCommand()
    {
        $processorNumber = Machine::getInstance()->detectProcessorNumber();
        $jobs = is_numeric($processorNumber) ? "--jobs $processorNumber" : "";
        $this->assertTrue($this->runCommand("phpbrew --quiet install $jobs {$this->primaryVersion} +default +intl"));
        $this->assertListContains($this->primaryVersion);
    }

    /**
     * @outputBuffering enabled
     * @depends testInstallCommand
     */
    public function testUseCommand()
    {
        $this->assertTrue($this->runCommand("phpbrew use {$this->primaryVersion}"));
    }

    /**
     * @outputBuffering enabled
     * @depends testInstallCommand
     */
    public function testCtagsCommand()
    {
        $this->assertTrue($this->runCommand("phpbrew ctags {$this->primaryVersion}"));
    }

    /**
     * @outputBuffering enabled
     * @depends testInstallCommand
     */
    public function testInstallAsCommand() {
        $processorNumber = Machine::getInstance()->detectProcessorNumber();
        $jobs = is_numeric($processorNumber) ? "--jobs $processorNumber" : "";
        $this->assertTrue($this->runCommand("phpbrew --quiet install $jobs {$this->primaryVersion} as myphp +soap"));
        $this->assertListContains("myphp");
    }

    /**
     * @outputBuffering enabled
     * @depends testInstallCommand
     */
    public function testCleanCommand()
    {
        $this->assertTrue($this->runCommand("phpbrew --quiet clean {$this->primaryVersion}"));
    }

    protected function assertListContains($string) {
        ob_start();
        $this->runCommandWithStdout("phpbrew list --dir --variants");
        $output = ob_get_clean();
        $this->assertContains($string, $output);
    }
}
