<?php
namespace PhpBrew\Testing;
use CLIFramework\Testing\CommandTestCase as BaseCommandTestCase;
use PhpBrew\Console;

abstract class CommandTestCase extends BaseCommandTestCase
{

    public function setupApplication() {
        $console = new Console;
        $console->getLogger()->setQuiet();
        return $console;
    }

    public function setUp() {
        parent::setUp();
        putenv('PHPBREW_HOME=' . getcwd() . '/.phpbrew');
        putenv('PHPBREW_ROOT=' . getcwd() . '/.phpbrew');
    }

}

