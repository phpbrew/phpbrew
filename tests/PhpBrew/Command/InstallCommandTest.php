<?php
use CLIFramework\Testing\CommandTestCase;

class InstallCommandTest extends CommandTestCase
{

    public function setupApplication() {
        return new PhpBrew\Console;
    }


    public function testInstallCommand()
    {
        $ret = $this->runCommand('phpbrew install 5.4 +default');
    }
}

