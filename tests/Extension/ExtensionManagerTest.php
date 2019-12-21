<?php

namespace PHPBrew\Tests\Extension;

use CLIFramework\Logger;
use PHPBrew\Extension\ExtensionFactory;
use PHPBrew\Extension\ExtensionManager;
use PHPBrew\Testing\VCRAdapter;
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
        $this->assertTrue($this->manager->cleanExtension($ext));
    }
}
