<?php
namespace PhpBrew\Command;

use CLIFramework\Command;
use PhpBrew\Config;

class PathCommand extends Command
{
    public function brief()
    {
        return 'Show paths of the current PHP.';
    }

    public function usage()
    {
        return 'phpbrew path [' 
            . join(', ', array('root', 'home','build','bin','include', 'etc', 'ext', 'ext-src', 'extension-src', 'extension-dir', 'config-scan', 'dist'))
            . ']';
    }

    public function arguments($args)
    {
        $args->add('type')
            ->validValues(array(
                'root', 'home','build','bin','include', 'etc', 'ext', 'ext-src', 'extension-src', 'extension-dir', 'config-scan', 'dist'
            ));
    }

    public function execute($name)
    {
        switch ($name) {
            case 'root':
                echo Config::getPhpbrewRoot();
                break;
            case 'home':
                echo Config::getPhpbrewHome();
                break;
            case 'config-scan':
                echo Config::getCurrentPhpConfigScanPath();
                break;
            case 'dist':
                echo Config::getDistFileDir();
                break;
            case 'build':
                echo Config::getCurrentBuildDir();
                break;
            case 'bin':
                echo Config::getCurrentPhpBin();
                break;
            case 'include':
                echo Config::getVersionInstallPrefix(Config::getCurrentPhpName()) .
                    DIRECTORY_SEPARATOR . 'include';
                break;
            case 'extension-src':
            case 'ext-src':
                echo Config::getCurrentBuildDir() . DIRECTORY_SEPARATOR . 'ext';
                break;
            case 'extension-dir':
            case 'ext-dir':
                echo ini_get('extension_dir');
                break;
            case 'etc':
                echo Config::getVersionInstallPrefix(Config::getCurrentPhpName()) .
                    DIRECTORY_SEPARATOR . 'etc';
                break;
        }
    }
}
