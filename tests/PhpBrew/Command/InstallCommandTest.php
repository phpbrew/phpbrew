<?php
use CLIFramework\Testing\CommandTestCase;

class InstallCommandTest extends CommandTestCase
{

    public function setupApplication() {
        return new PhpBrew\Console;
    }

    public function testInstallCommand()
    {
        ob_start();
        ok($this->runCommand('phpbrew -d install 5.4.29 +default+iconv+intl'));
        ok($this->runCommand('phpbrew -d install --like 5.4.29 5.5 +intl'));
        ob_end_clean();
    }
}

