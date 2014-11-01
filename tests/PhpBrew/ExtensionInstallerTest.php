<?php

class ExtensionInstallerTest extends PHPUnit_Framework_TestCase
{

    public function testPackageUrl()
    {
        $logger = new CLIFramework\Logger;
        $downloader = new PhpBrew\Extension\PeclExtensionDownloader($logger);
        $extractPath = $downloader->download('APCu');
        $this->assertNotEmpty($extractPath);
    }

    public function packageNameProvider()
    {
        return array(
            // array('APC'),
            array('xdebug'),
            // array('yaml'),
        );
    }

    /**
     * @dataProvider packageNameProvider
     */
    public function testInstallPackages($packageName)
    {
        if ( ! file_exists('tmp') ) {
            mkdir('tmp');
        }
        chdir('tmp');
        $logger = new CLIFramework\Logger;
        $installer = new PhpBrew\Extension\ExtensionInstaller($logger);

        ob_start();
        $installedPath = $installer->installFromPecl($packageName);
        ob_end_clean();

        path_ok( $installedPath );
    }
}
