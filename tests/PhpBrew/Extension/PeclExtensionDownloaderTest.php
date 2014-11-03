<?php
namespace PhpBrew\Extension;
use PhpBrew\Config;
use PhpBrew\Extension;
use PhpBrew\Extension\ExtensionManager;
use PhpBrew\Extension\ExtensionFactory;
use PhpBrew\Extension\PeclExtensionInstaller;
use PhpBrew\Extension\PeclExtensionDownloader;
use PhpBrew\Utils;
use PHPUnit_Framework_TestCase;
use CLIFramework\Logger;;
use GetOptionKit\OptionResult;

class ExtensionInstallerTest extends PHPUnit_Framework_TestCase
{
    public function testPackageUrl()
    {
        $logger = new Logger;
        $logger->setQuiet();
        $downloader = new PeclExtensionDownloader($logger, new OptionResult);
        $extractPath = $downloader->download('APCu', 'latest');
        path_ok($extractPath);
    }

    public function packageNameProvider()
    {
        return array(
            array('xdebug'),
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
        $peclDownloader = new PeclExtensionDownloader($logger, new OptionResult);
        $peclDownloader->download($extensionName, 'latest');
        $ext = ExtensionFactory::lookup($extensionName);
        $manager->installExtension($ext, array());
    }
}
