<?php
namespace PhpBrew\Patch;

use PhpBrew\Config;
use PhpBrew\Build;
use PhpBrew\Testing\TemporaryFileFixture;
use CLIFramework\Logger;

/**
 * @small
 */
class RegexpPatchTest extends \PHPUnit_Framework_TestCase
{
    public $logger;

    public function setUp()
    {
        $this->logger = new Logger();
        $this->logger->setQuiet();
    }

    public function testEnableBackup()
    {
        $version = '5.3.29';
        $originalFixturePath = getenv('PHPBREW_FIXTURES_PHP_DIR') . "/$version/Makefile";
        $fixture = new TemporaryFileFixture($this, $originalFixturePath);
        $fixture->withFile(
            'Makefile',
            function($self, $fixturePath) use ($version, $fixture, $originalFixturePath) {
                $build = new Build($version);
                $build->setSourceDirectory(dirname($fixturePath));
                $patch = new RegexpPatch(
                    $self->logger,
                    $build,
                    array(basename($fixturePath)),
                    array()
                );
                $patch->enableBackup();
                $patch->apply();
                $self->assertFileEquals(
                    $originalFixturePath,
                    $fixture->getTemporaryDirectory() . '/Makefile.bak'
                );
            }
        );
    }

    public function testApply()
    {
        $version = '5.3.29';
        $fixture = new TemporaryFileFixture($this, getenv('PHPBREW_FIXTURES_PHP_DIR') . "/$version/Makefile.in");
        $fixture->withFile(
            'Makefile',
            function($self, $fixturePath) use ($version) {
                $build = new Build($version);
                $build->setSourceDirectory(dirname($fixturePath));
                $patch = new RegexpPatch(
                    $self->logger,
                    $build,
                    array(basename($fixturePath)),
                    array(RegexpPatchRule::always('/LIBTOOL/', ''))
                );
                $patch->apply();
                $self->assertFileEquals(
                    $fixturePath,
                    getenv('PHPBREW_EXPECTED_PHP_DIR') . '/5.3.29/Makefile.in'
                );
            }
        );
    }
}
