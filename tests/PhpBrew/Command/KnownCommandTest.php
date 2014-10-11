<?php
use CLIFramework\Testing\CommandTestCase;

class KnownCommandTest extends CommandTestCase
{

    public function setupApplication() {
        return new PhpBrew\Console;
    }

    public function testKnownCommand()
    {
        ob_start();
        ok($this->runCommand('phpbrew known --update'));
        ok($this->runCommand('phpbrew known -u'));
        ok($this->runCommand('phpbrew known'));
        ok($this->runCommand('phpbrew known --more'));
        ok($this->runCommand('phpbrew known --old --more'));
        ob_end_clean();
    }
}

