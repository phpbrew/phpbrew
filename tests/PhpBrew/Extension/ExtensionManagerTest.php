<?php

namespace PhpBrew\Tests\Extension;

use CLIFramework\Logger;
use PhpBrew\Extension\ExtensionFactory;
use PhpBrew\Extension\ExtensionManager;
use PhpBrew\Testing\VCRAdapter;
use PHPUnit\Framework\TestCase;

/**
 * ExtensionManagerTest
 *
 * @large
 * @group extension
 */
class ExtensionManagerTest extends TestCase
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
