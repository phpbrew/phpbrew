<?php
namespace PhpBrew\Tasks;

use PhpBrew\Config;
use PhpBrew\Build;
use PhpBrew\Testing\TemporaryFileFixture;
use GetOptionKit\OptionResult;
use CLIFramework\Logger;

class Apxs2PatchTaskTest extends \PHPUnit_Framework_TestCase
{
    public $logger;

    public function setUp()
    {
        $this->logger = new Logger();
        $this->logger->setQuiet();
    }

    public function test()
    {
        $build = new Apxs2PatchTaskTestBuild();
        $makefileFixture = new TemporaryFileFixture(
            $this,
            $build->getMakefileSourcePath()
        );
        $configureFixture = new TemporaryFileFixture(
            $this,
            $build->getConfigureSourcePath()
        );
        $makefileFixture->setTemporaryDirectory($build->getSourceDirectory());
        $configureFixture->setTemporaryDirectory($build->getSourceDirectory());

        $makefileFixture->withFile(
            'Makefile.global',
            function($self, $makefileFixturePath) use ($build, $configureFixture) {
                $configureFixture->withFile(
                    'configure',
                    function($self, $configureFixturePath) use ($build, $makefileFixturePath) {
                        $task = new Apxs2PatchTask($self->logger);
                        $task->patch($build, new OptionResult());

                        $self->assertFileEquals(
                            getenv('PHPBREW_EXPECTED_PHP_DIR') . '/5.5.19/Makefile.global',
                            $makefileFixturePath
                        );
                        $self->assertFileEquals(
                            getenv('PHPBREW_EXPECTED_PHP_DIR') . '/5.5.19/configure',
                            $configureFixturePath
                        );
                    }
                );
            }
        );
    }
}

class Apxs2PatchTaskTestBuild extends Build
{
    public function __construct()
    {
        parent::__construct('5.5.19');
        $this->setSourceDirectory(Config::getTempFileDir() . '/' . uniqid());
    }

    public function getFixtureDirectory()
    {
        return getenv('PHPBREW_FIXTURES_PHP_DIR') . '/' . $this->getVersion();
    }

    public function getMakefileSourcePath()
    {
        return $this->getFixtureDirectory() . '/Makefile.global';
    }

    public function getConfigureSourcePath()
    {
        return $this->getFixtureDirectory() . '/configure';
    }

    public function getMakefileDestPath()
    {
        return  $this->getSourceDirectory() . '/Makefile.global';
    }

    public function getConfigureDestPath()
    {
        return  $this->getSourceDirectory() . '/configure';
    }
}
