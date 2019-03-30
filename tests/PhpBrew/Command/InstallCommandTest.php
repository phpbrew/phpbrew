<?php
use PhpBrew\Testing\CommandTestCase;
use PhpBrew\Machine;
use PhpBrew\Config;
use PhpBrew\BuildFinder;

/**
 * The install command tests are heavy.
 *
 * Don't catch the exceptions, the system command exception
 * will show up the error message.
 *
 * Build output will be shown when assertion failed.
 *
 * @large
 * @group command
 * @group noVCR
 */
class InstallCommandTest extends CommandTestCase
{
    public $usesVCR = false;

    /**
     * @group install
     */
    public function testKnownCommand()
    {
        $this->assertCommandSuccess("phpbrew init");
        $this->assertCommandSuccess("phpbrew known --update");
    }


    /**
     * @depends testKnownCommand
     * @group install
     */
    public function testInstallCommand()
    {
        if (getenv('TRAVIS')) {
            $this->markTestSkipped('Skip heavy test on Travis');
        }

        $versionName = $this->getPrimaryVersion();
        $processorNumber = Machine::getInstance()->detectProcessorNumber();
        $jobs = is_numeric($processorNumber) ? "--jobs $processorNumber" : "";
        $this->assertCommandSuccess("phpbrew install $jobs php-{$versionName} +cli+posix+intl+gd");
        $this->assertListContains("php-{$versionName}");
    }

    /**
     * @depends testInstallCommand
     */
    public function testEnvCommand()
    {
        $versionName = $this->getPrimaryVersion();
        $this->assertCommandSuccess("phpbrew env php-{$versionName}");
    }

    /**
     * @depends testInstallCommand
     * @group mayignore
     */
    public function testCtagsCommand()
    {
        $versionName = $this->getPrimaryVersion();
        $this->assertCommandSuccess("phpbrew ctags php-{$versionName}");
    }

    /**
     * @group install
     * @group mayignore
     */
    public function testGitHubInstallCommand()
    {
        if (getenv('TRAVIS')) {
            $this->markTestSkipped('Skip heavy test on Travis');
        }

        $this->assertCommandSuccess("phpbrew --debug install --dryrun github:php/php-src@PHP-7.0 as php-7.0.0 +cli+posix");
    }

    /**
     * @depends testInstallCommand
     * @group install
     */
    public function testInstallAsCommand()
    {
        $versionName = $this->getPrimaryVersion();
        $processorNumber = Machine::getInstance()->detectProcessorNumber();
        $jobs = is_numeric($processorNumber) ? "--jobs $processorNumber" : "";
        $this->assertCommandSuccess("phpbrew install {$jobs} php-{$versionName} as myphp +cli+soap");
        $this->assertListContains("myphp");
    }

    /**
     * @depends testInstallCommand
     */
    public function testCleanCommand()
    {
        $versionName = $this->getPrimaryVersion();
        $this->assertCommandSuccess("phpbrew clean php-{$versionName}");
    }

    protected function assertListContains($string)
    {
        $this->assertNotEmpty(BuildFinder::findInstalledBuilds(false), 'findInstalledBuilds');
        $this->assertContains($string, BuildFinder::findInstalledBuilds(false));
    }
}
