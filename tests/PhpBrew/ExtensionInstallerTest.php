<?php

class ExtensionInstallerTest extends PHPUnit_Framework_TestCase
{

    public function testPackageUrl()
    {
        $logger = new CLIFramework\Logger;
        $installer = new PhpBrew\ExtensionInstaller($logger);

        $url = $installer->findPeclPackageUrl('APC');
        $this->assertNotEmpty($url);
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
        $installer = new PhpBrew\ExtensionInstaller($logger);

        ob_start();
        $installedPath = $installer->installFromPecl($packageName);
        ob_end_clean();

        path_ok( $installedPath );
    }
}
