<?php
use PhpBrew\Testing\CommandTestCase;
use PhpBrew\Machine;
use PhpBrew\Config;

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
        $versionName = $this->getPrimaryVersion();
        $processorNumber = Machine::getInstance()->detectProcessorNumber();
        $jobs = is_numeric($processorNumber) ? "--jobs $processorNumber" : "";
        $this->assertTrue($this->runCommand("phpbrew --quiet install $jobs {$versionName} +default +intl"));
        $this->assertListContains("php-{$versionName}");
    }

    /**
     * @outputBuffering enabled
     * @depends testInstallCommand
     */
    public function testUseCommand()
    {
        $versionName = $this->getPrimaryVersion();
        $this->assertTrue($this->runCommand("phpbrew use {$versionName}"));
    }

    /**
     * @outputBuffering enabled
     * @depends testInstallCommand
     */
    public function testCtagsCommand()
    {
        $versionName = $this->getPrimaryVersion();
        $this->assertTrue($this->runCommand("phpbrew ctags {$versionName}"));
    }

    /**
     * @outputBuffering enabled
     * @depends testInstallCommand
     */
    public function testInstallAsCommand()
    {
        $versionName = $this->getPrimaryVersion();
        $processorNumber = Machine::getInstance()->detectProcessorNumber();
        $jobs = is_numeric($processorNumber) ? "--jobs $processorNumber" : "";
        $this->assertTrue($this->runCommand("phpbrew --quiet install {$jobs} {$versionName} as myphp +soap"));
        $this->assertListContains("myphp");
    }

    /**
     * @outputBuffering enabled
     * @depends testInstallCommand
     */
    public function testCleanCommand()
    {
        $versionName = $this->getPrimaryVersion();
        $this->assertTrue($this->runCommand("phpbrew --quiet clean {$versionName}"));
    }

    protected function assertListContains($string)
    {
        $this->assertContains($string, Config::getInstalledPhpVersions());
    }
}
