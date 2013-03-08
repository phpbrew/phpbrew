<?php

class ExtensionInstallerTest extends PHPUnit_Framework_TestCase
{

    public function testPackageUrl()
    {
        $logger = new CLIFramework\Logger;
        $installer = new PhpBrew\ExtensionInstaller($logger);
        ok($installer);

        $url = $installer->findPeclPackageUrl('APC');
        ok($url);
    }


    public function packageNameProvider()
    {
        return array( 
            array('APC'),
            array('xdebug'),
            // array('yaml'),
        );
    }


    /**
     * @dataProvider packageNameProvider
     */
    public function testInstallPackages($packageName)
    {
        if( ! file_exists('tmp') ) {
            mkdir('tmp');
        }
        chdir('tmp');
        $logger = new CLIFramework\Logger;
        $installer = new PhpBrew\ExtensionInstaller($logger);
        ok($installer);
        $installedPath = $installer->install($packageName);
        chdir('..');
        path_ok( $installedPath );
    }
}

