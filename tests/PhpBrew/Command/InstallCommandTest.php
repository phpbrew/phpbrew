<?php
use CLIFramework\Testing\CommandTestCase;

class InstallCommandTest extends CommandTestCase
{

    public function setupApplication() {
        $console = new PhpBrew\Console;
        $console->getLogger()->setQuiet();
        return $console;
    }

    /**
     * @outputBuffering enabled
     */
    public function testInstallCommand()
    {
        $this->assertTrue($this->runCommand('phpbrew --quiet install 5.4.29'));
        $this->assertTrue($this->runCommand('phpbrew --quiet install --like 5.4.29 5.5 +soap'));
    }
}

