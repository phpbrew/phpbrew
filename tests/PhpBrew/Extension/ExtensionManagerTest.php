<?php
namespace PhpBrew\Extension;

use PhpBrew\Buildable;
use CLIFramework\Logger;
use PhpBrew\Testing\VCRAdapter;

/**
 * ExtensionManagerTest
 *
 * @large
 * @group extension
 */
class ExtensionManagerTest extends \PHPUnit\Framework\TestCase
{
    private $manager;

    public function setUp()
    {
        $logger = new Logger();
        $logger->setQuiet();
        $this->manager = new ExtensionManager($logger);

        VCRAdapter::enableVCR($this);
    }

    public function tearDown()
    {
        VCRAdapter::disableVCR();
    }

    public function testCleanExtension()
    {
        $ext = ExtensionFactory::lookup('xdebug', array(getenv('PHPBREW_EXTENSION_DIR')));
        $this->manager->cleanExtension($ext);
    }
}
