<?php
namespace PhpBrew\Tasks;
use PhpBrew\CommandBuilder;
use PhpBrew\Config;

/**
 * Task to run `make install`
 */
class InstallTask
{
    public function install()
    {
        $this->info("Installing...");
        $install = new CommandBuilder('make install');
        $install->append = true;
        $install->stdout = Config::getVersionBuildLogPath( $version );
        $install->execute() !== false or die('Install failed.');
    }
}


