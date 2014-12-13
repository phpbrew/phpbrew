<?php
namespace PhpBrew\Extension;
use PhpBrew\Buildable;
use CLIFramework\Logger;

/**
 * ExtensionManagerTest
 *
 * @large
 * @group extension
 */
class ExtensionManagerTest extends \PHPUnit_Framework_TestCase
{
    private $manager;

    public function setUp()
    {
        $logger = new Logger();
        $logger->setQuiet();
        $this->manager = new ExtensionManager($logger);
    }

    public function testCleanExtension()
    {
        $ext = ExtensionFactory::lookup('xdebug', array(getenv('PHPBREW_EXTENSION_DIR')));
        $this->manager->cleanExtension($ext);
    }
}
