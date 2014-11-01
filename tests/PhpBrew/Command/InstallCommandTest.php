<?php
use CLIFramework\Testing\CommandTestCase;

class InstallCommandTest extends CommandTestCase
{

    public function setupApplication() {
        return new PhpBrew\Console;
    }

    /**
     * @outputBuffering enabled
     */
    public function testInstallCommand()
    {
        $this->assertTrue($this->runCommand('phpbrew -q install -q 5.4.29'));
        $this->assertTrue($this->runCommand('phpbrew -q install -q --like 5.4.29 5.5 +soap'));
    }
}

