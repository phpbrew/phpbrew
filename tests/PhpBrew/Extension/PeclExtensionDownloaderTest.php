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
        $peclHosting = new Extension\Hosting\Pecl;
        $downloader = new ExtensionDownloader($logger, new OptionResult);
        $peclHosting->setPackageName('APCu');
        $extractPath = $downloader->download($peclHosting, 'latest');
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
        $peclHosting = new Extension\Hosting\Pecl;
        $downloader = new ExtensionDownloader($logger, new OptionResult);
        $peclHosting->setPackageName($extensionName);
        $downloader->download($peclHosting, 'latest');
        $ext = ExtensionFactory::lookup($extensionName);
        $manager->installExtension($ext, array());
    }
}
