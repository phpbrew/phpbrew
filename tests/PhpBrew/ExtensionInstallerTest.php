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
        // XXX: skip for now
        return;

        // only works for php5.4
        if ( version_compare(phpversion(), '5.5') != -1 ) {
            // need 5.4 or 5.3 to test
            return;
        }

        if( ! file_exists('tmp') ) {
            mkdir('tmp');
        }
        chdir('tmp');
        $logger = new CLIFramework\Logger;
        $installer = new PhpBrew\ExtensionInstaller($logger);
        ok($installer);
        $installedPath = $installer->installFromPecl($packageName);
        chdir('..');
        path_ok( $installedPath );
    }
}

