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
        $this->assertTrue($this->runCommand("phpbrew --quiet install 5.4.35 +default +intl"));
        $this->assertListContains("5.4.35");
    }

    /**
     * @outputBuffering enabled
     * @depends testInstallCommand
     */
    public function testUseCommand()
    {
        $this->assertTrue($this->runCommand("phpbrew use 5.4.35"));
    }

    /**
     * @outputBuffering enabled
     * @depends testInstallCommand
     */
    public function testCtagsCommand()
    {
        $this->assertTrue($this->runCommand("phpbrew ctags 5.4.35"));
    }

    /**
     * @outputBuffering enabled
     * @depends testInstallCommand
     */
    public function testInstallLikeCommand() {
        $this->assertTrue($this->runCommand("phpbrew --quiet install 5.4.35 as myphp +soap"));
        $this->assertListContains("myphp");
    }

    /**
     * @outputBuffering enabled
     * @depends testInstallCommand
     */
    public function testCleanCommand()
    {
        $this->assertTrue($this->runCommand("phpbrew --quiet clean 5.4.35"));
    }

    protected function assertListContains($string){
        ob_start();
        $this->runCommandWithStdout("phpbrew list --dir --variants");
        $output = ob_get_clean();
        $this->assertContains($string, $output);
    }
}
