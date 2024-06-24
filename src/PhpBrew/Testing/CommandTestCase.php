<?php

namespace PhpBrew\Testing;

use CurlKit\CurlException;
use CLIFramework\Testing\CommandTestCase as BaseCommandTestCase;
use GetOptionKit\Option;
use PhpBrew\Console;

abstract class CommandTestCase extends BaseCommandTestCase
{
    protected $debug = false;

    private $previousPhpBrewRoot;

    private $previousPhpBrewHome;

    public $primaryVersion = '7.0.33';

    /**
     * You need to set this to true in each subclass you want to use VCR in.
     */
    public $usesVCR = false;

    public function getPrimaryVersion()
    {
        /*
        if ($version = getenv('TRAVIS_PHP_VERSION')) {
            return "php-$version";
        }
        */
        return $this->primaryVersion;
    }

    public function setupApplication()
    {
        $console = Console::getInstance();
        $console->getLogger()->setQuiet();
        $console->getFormatter()->preferRawOutput();

        return $console;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousPhpBrewRoot = getenv('PHPBREW_ROOT');
        $this->previousPhpBrewHome = getenv('PHPBREW_HOME');

        // <env name="PHPBREW_ROOT" value=".phpbrew"/>
        // <env name="PHPBREW_HOME" value=".phpbrew"/>

        // already setup in phpunit.xml, but it seems don't work.
        // putenv('PHPBREW_ROOT=' . getcwd() . '/.phpbrew');
        // putenv('PHPBREW_HOME=' . getcwd() . '/.phpbrew');

        if ($options = Console::getInstance()->options) {
            $option = new Option('no-progress');
            $option->setValue(true);
            $options->set('no-progress', $option);
        }

        if ($this->usesVCR) {
            VCRAdapter::enableVCR($this);
        }
    }

    /*
     * we don't have to restore it back. the parent environment variables
     * won't change if the they are changed inside a process.
     * but we might want to change it back if there is a test changed the environment variable.
     */
    protected function tearDown(): void
    {
        if ($this->previousPhpBrewRoot !== null) {
            // putenv('PHPBREW_ROOT=' . $this->previousPhpBrewRoot);
        }
        if ($this->previousPhpBrewHome !== null) {
            // putenv('PHPBREW_HOME=' . $this->previousPhpBrewHome);
        }

        if ($this->usesVCR) {
            VCRAdapter::disableVCR();
        }
    }

    public function assertCommandSuccess($args)
    {
        try {
            if ($this->debug) {
                fwrite(STDERR, $args . PHP_EOL);
            }

            ob_start();
            $ret = parent::runCommand($args);
            $output = ob_get_contents();
            ob_end_clean();

            $this->assertTrue($ret, $output);
        } catch (CurlException $e) {
            $this->markTestIncomplete($e->getMessage());
        }
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
