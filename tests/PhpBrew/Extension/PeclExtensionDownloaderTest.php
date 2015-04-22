<?php
namespace PhpBrew\Extension;
use PhpBrew\Config;
use PhpBrew\Extension;
use PhpBrew\Extension\ExtensionManager;
use PhpBrew\Extension\ExtensionFactory;
use PhpBrew\Extension\PeclExtensionInstaller;
use PhpBrew\Extension\ExtensionDownloader;
use PhpBrew\Utils;
use PHPUnit_Framework_TestCase;
use CLIFramework\Logger;;
use GetOptionKit\OptionResult;
use PhpBrew\Extension\Provider\PeclProvider;

/**
 * @large
 * @group extension
 */
class ExtensionInstallerTest extends PHPUnit_Framework_TestCase
{
    public function testPackageUrl()
    {
        $logger = new Logger;
        $logger->setQuiet();
        $peclProvider = new PeclProvider;
        $downloader = new ExtensionDownloader($logger, new OptionResult);
        $peclProvider->setPackageName('APCu');
        $extractPath = $downloader->download($peclProvider, 'latest');
        path_ok($extractPath);
    }

    public function packageNameProvider()
    {
        return array(
            array('xdebug', '2.3.2'),
            // array('APCu'),
            // array('yaml'),
        );
    }

    /**
     * @dataProvider packageNameProvider
     */
    public function testInstallPackages($extensionName, $extensionVersion = 'latest')
    {
        $logger = new Logger;
        $logger->setQuiet();
        $manager = new ExtensionManager($logger);
        $peclProvider = new PeclProvider;
        $downloader = new ExtensionDownloader($logger, new OptionResult);
        $peclProvider->setPackageName($extensionName);
        $downloader->download($peclProvider, $extensionVersion);
        $ext = ExtensionFactory::lookup($extensionName);
        $this->assertNotNull($ext);
        $manager->installExtension($ext, array());
    }
}
