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
     * @group install
     */
    public function testKnownCommand()
    {
        $this->assertCommandSuccess("phpbrew init");
        $this->assertCommandSuccess("phpbrew known --update");
    }

    /**
     * @outputBuffering enabled
     * @depends testKnownCommand
     * @group install
     */
    public function testInstallCommand()
    {
        $versionName = $this->getPrimaryVersion();
        $processorNumber = Machine::getInstance()->detectProcessorNumber();
        $jobs = is_numeric($processorNumber) ? "--jobs $processorNumber" : "";
        $this->assertCommandSuccess("phpbrew --quiet install $jobs {$versionName} +default +intl");
        $this->assertListContains("php-{$versionName}");
    }

    /**
     * @outputBuffering enabled
     * @depends testInstallCommand
     */
    public function testUseCommand()
    {
        $versionName = $this->getPrimaryVersion();
        $this->assertCommandSuccess("phpbrew use {$versionName}");
    }

    /**
     * @outputBuffering enabled
     * @depends testInstallCommand
     */
    public function testCtagsCommand()
    {
        $versionName = $this->getPrimaryVersion();
        $this->assertCommandSuccess("phpbrew ctags {$versionName}");
    }

    /**
     * @outputBuffering enabled
     * @depends testInstallCommand
     * @group install
     */
    public function testInstallAsCommand()
    {
        $versionName = $this->getPrimaryVersion();
        $processorNumber = Machine::getInstance()->detectProcessorNumber();
        $jobs = is_numeric($processorNumber) ? "--jobs $processorNumber" : "";
        $this->assertCommandSuccess("phpbrew --quiet install {$jobs} {$versionName} as myphp +soap");
        $this->assertListContains("myphp");
    }

    /**
     * @outputBuffering enabled
     * @depends testInstallCommand
     */
    public function testCleanCommand()
    {
        $versionName = $this->getPrimaryVersion();
        $this->assertCommandSuccess("phpbrew --quiet clean {$versionName}");
    }

    protected function assertListContains($string)
    {
        $this->assertNotEmpty(Config::getInstalledPhpVersions());
        $this->assertContains($string, Config::getInstalledPhpVersions());
    }
}
