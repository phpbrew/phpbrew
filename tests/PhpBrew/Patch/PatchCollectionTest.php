<?php
namespace PhpBrew\Patch;

use PhpBrew\Config;
use PhpBrew\Build;
use PhpBrew\Testing\TemporaryFileFixture;
use CLIFramework\Logger;

class PatchCollectionTest extends \PHPUnit_Framework_TestCase
{
    public $logger;
    public $build;
    public $collection;

    public function setUp()
    {
        $this->logger = new Logger();
        $this->logger->setQuiet();
        $version = '5.5.19';
        $this->build = new Build($version);
        $this->build->setSourceDirectory(getenv('PHPBREW_FIXTURES_PHP_DIR') . DIRECTORY_SEPARATOR . $version);
        $this->collection = new PatchCollection($this->logger, $this->build);
    }

    public function testCreatePathcesFor64BitSupport()
    {
        is(1, count(PatchCollection::createPatchesFor64BitSupport($this->logger, $this->build)));
    }

    public function testCreatePathcesForApxs2()
    {
        is(1, count(PatchCollection::createPatchesForApxs2($this->logger, $this->build)));
    }
}
