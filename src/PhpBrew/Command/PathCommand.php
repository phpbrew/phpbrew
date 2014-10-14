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

    public function execute($name)
    {
        switch ($name) {
            case 'home':
                echo Config::getPhpbrewRoot();
                break;
            case 'build':
                echo Config::getBuildDir();
                break;
            case 'bin':
                echo Config::getCurrentPhpBin();
                break;
            case 'include':
                echo Config::getVersionInstallPrefix(Config::getCurrentPhpName()) .
                    DIRECTORY_SEPARATOR . 'include';
                break;
            case 'etc':
                echo Config::getVersionInstallPrefix(Config::getCurrentPhpName()) .
                    DIRECTORY_SEPARATOR . 'etc';
                break;
        }
    }
}
