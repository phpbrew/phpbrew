<?php
namespace PhpBrew\Testing;
use CLIFramework\Testing\CommandTestCase as BaseCommandTestCase;
use PhpBrew\Console;
use Exception;

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

        // XXX: already setup in phpunit.xml
        // putenv('PHPBREW_ROOT=' . getcwd() . '/tests/.phpbrew');
        // putenv('PHPBREW_HOME=' . getcwd() . '/tests/.phpbrew');
    }

    /*
     * XXX: we don't have to restore it back. the parent environment variables
     *      won't change if the they are changed inside a process.
    public function tearDown()
    {
        if ($this->previousPhpBrewRoot !== null) {
            putenv('PHPBREW_ROOT=' . $this->previousPhpBrewRoot);
        }
        if ($this->previousPhpBrewHome !== null) {
            putenv('PHPBREW_HOME=' . $this->previousPhpBrewHome);
        }
    }
     */

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
