<?php
use CLIFramework\Testing\CommandTestCase;

class InstallCommandTest extends CommandTestCase
{

    public function setupApplication() {
        return new PhpBrew\Console;
    }


    public function testInstallCommand()
    {
        // suppress output messages
        ob_start();
        ok($this->runCommand('phpbrew install 5.4 +default'));
        ob_end_clean();
    }
}

