<?php
namespace PhpBrew\Tasks;

use PhpBrew\Config;
use PhpBrew\Build;
use PhpBrew\Testing\TemporaryFileFixture;
use CLIFramework\Logger;
use GetOptionKit\OptionResult;

/**
 * @small
 */
class Patch64BitSupportTaskTest extends \PHPUnit_Framework_TestCase
{
    public $logger;

    public function setUp()
    {
        $this->logger = new Logger();
        $this->logger->setQuiet();
    }

    public function test()
    {
        $build = new Patch64BitSupportTaskTestBuild();
        $fixture = new TemporaryFileFixture(
            $this,
            getenv('PHPBREW_FIXTURES_PHP_DIR') . '/' . $build->getVersion() . '/Makefile'
        );
        $fixture->setTemporaryDirectory($build->getSourceDirectory());
        $fixture->withFile(
            'Makefile',
            function($self, $fixturePath) use ($build) {
                $task = new Patch64BitSupportTask($self->logger, new OptionResult());
                $task->patch($build);
                $self->assertFileEquals(
                    getenv('PHPBREW_EXPECTED_PHP_DIR') . '/' . $build->getVersion() . '/Makefile',
                    $fixturePath
                );
            }
        );
    }
}

class Patch64BitSupportTaskTestBuild extends Build
{
    public function __construct()
    {
        parent::__construct('5.3.29');
        $this->setSourceDirectory(Config::getTempFileDir() . '/' . uniqid());
    }
}
