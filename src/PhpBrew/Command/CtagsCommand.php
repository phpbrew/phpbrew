<?php
namespace PhpBrew\Command;
use PhpBrew\Config;
use PhpBrew\Build;
use Exception;

class CtagsCommand extends \CLIFramework\Command
{
    public function brief() { return 'Run ctags at current php source dir for extension development.'; }

    public function execute( $version = null )
    {
        // $currentVersion;
        $root = Config::getPhpbrewRoot();
        $home = Config::getPhpbrewHome();
        $buildDir = Config::getBuildDir();

        if ( ! $version ) {
            $version = getenv('PHPBREW_PHP');
        }

        $versionBuildSource = $buildDir . DIRECTORY_SEPARATOR . $version;
        echo $versionBuildSource;

        $cmd = new CommandBuilder;

        system('ctags');
    }
}
