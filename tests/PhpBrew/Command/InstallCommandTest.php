<?php
use CLIFramework\Testing\CommandTestCase;

class InstallCommandTest extends CommandTestCase
{

    public function setupApplication() {
        $console = new PhpBrew\Console;
        $console->getLogger()->setQuiet();
        return $console;
    }

    public function testInstallCommandLatestMinorVersion() {
        $this->assertTrue($this->runCommand('phpbrew --quiet install -d 5.4'));
    }

    /**
     * @outputBuffering enabled
     */
    public function testInstallCommand()
    {
        $this->assertTrue($this->runCommand('phpbrew --quiet install -d 5.4.29'));
        $this->assertTrue($this->runCommand('phpbrew --quiet install -d --like 5.4.29 5.5 +soap'));
    }
}

