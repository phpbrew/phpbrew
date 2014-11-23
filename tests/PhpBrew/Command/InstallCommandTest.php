<?php
use PhpBrew\Testing\CommandTestCase;

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
        $this->assertTrue($this->runCommand("phpbrew --quiet install 5.4.29 +default +intl"));
        $this->assertListContains("5.4.29");
    }

    /**
     * @outputBuffering enabled
     * @depends testInstallCommand
     */
    public function testUseCommand()
    {
        $this->assertTrue($this->runCommand("phpbrew use 5.4.29"));
        $this->assertListContains("*\t5.4.29", $output);
    }

    /**
     * @outputBuffering enabled
     * @depends testInstallCommand
     */
    public function testCtagsCommand()
    {
        $this->assertTrue($this->runCommand("phpbrew ctags 5.4.29"));
    }

    /**
     * @outputBuffering enabled
     * @depends testInstallCommand
     */
    public function testInstallLikeCommand() {
        $this->assertTrue($this->runCommand("phpbrew --quiet install -d --like myPHP 5.5.18 +soap"));
        $this->assertListContains("myPHP", $output);
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

    protected function assertListContains($string){
        ob_start();
        $this->runCommandWithStdout("phpbrew list --dir --variants");
        $output = ob_get_clean();
        $this->assertContains($string, $output);
    }
}
