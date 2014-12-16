<?php
namespace PhpBrew\Testing;
use CLIFramework\Testing\CommandTestCase as BaseCommandTestCase;
use PhpBrew\Console;

abstract class CommandTestCase extends BaseCommandTestCase
{
    private $previousPhpBrewRoot;
    private $previousPhpBrewHome;

    public function setupApplication()
    {
        $console = Console::getInstance();
        $console->getLogger()->setQuiet();
        $console->getFormatter()->preferRawOutput();
        return $console;
    }

    public function setUp()
    {
        parent::setUp();
        $this->previousPhpBrewRoot = getenv('PHPBREW_ROOT');
        $this->previousPhpBrewHome = getenv('PHPBREW_HOME');
        putenv('PHPBREW_ROOT=' . getcwd() . '/tests/.phpbrew');
        putenv('PHPBREW_HOME=' . getcwd() . '/tests/.phpbrew');
    }

    public function tearDown()
    {
        putenv('PHPBREW_ROOT=' . $this->previousPhpBrewRoot);
        putenv('PHPBREW_HOME=' . $this->previousPhpBrewHome);
    }

    public function runCommand($args)
    {
        ob_start();
        $status = parent::runCommand($args);
        ob_end_clean();

        return $status;
    }

    public function runCommandWithStdout($args)
    {
        return parent::runCommand($args);
    }

}
