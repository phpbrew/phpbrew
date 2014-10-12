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
        $this->assertTrue($this->runCommand('phpbrew -d install 5.4.29 +default+iconv+intl'));
        $this->assertTrue($this->runCommand('phpbrew -d install --like 5.4.29 5.5 +intl'));
    }
}

