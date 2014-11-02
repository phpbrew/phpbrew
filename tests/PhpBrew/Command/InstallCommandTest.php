<?php
use PhpBrew\Testing\CommandTestCase;

class InstallCommandTest extends CommandTestCase
{
    /**
     * @outputBuffering enabled
     */
    public function testInstallCommandLatestMinorVersion() {
        $this->assertTrue($this->runCommand("phpbrew --quiet install 5.4")); // we will likely get 5.4.34 - 2014-11-02
    }

    /**
     * @outputBuffering enabled
     * @depends testInstallCommandLatestMinorVersion
     */
    public function testInstallCommand()
    {
        $this->assertTrue($this->runCommand("phpbrew --quiet install 5.4.29 +sqlite +intl +icu"));
    }

    /**
     * @outputBuffering enabled
     * @depends testInstallCommand
     */
    public function testListCommand()
    {
        $this->assertTrue($this->runCommand("phpbrew list -v -d"));
        $this->assertTrue($this->runCommand("phpbrew list --dir --variants"));
    }

    /**
     * @outputBuffering enabled
     * @depends testListCommand
     * @depends testInstallCommand
     */
    public function testUseCommand()
    {
        $this->assertTrue($this->runCommand("phpbrew use 5.4.29"));
    }

    /**
     * @outputBuffering enabled
     * @depends testInstallCommand
     */
    public function testInstallLikeCommand() {
        $this->assertTrue($this->runCommand("phpbrew --quiet install -d --like 5.4.29 5.5.18 +soap"));
    }


    /**
     * @outputBuffering enabled
     * @depends testInstallCommand
     * @depends testUseCommand
     */
    public function testCleanCommand()
    {
        $this->assertTrue($this->runCommand("phpbrew --quiet clean 5.4.29"));
    }



}

