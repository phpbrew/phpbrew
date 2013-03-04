<?php
namespace PhpBrew\Tasks;
use PhpBrew\CommandBuilder;
use PhpBrew\Config;

/**
 * Task to run `make install`
 */
class InstallTask extends BaseTask
{
    public $logPath;

    public function setLogPath($path)
    {
        $this->logPath = $path;
    }

    public function install()
    {
        $this->info("Installing...");
        $install = new CommandBuilder('make install');
        $install->append = true;
        if($this->logPath) {
            $install->stdout = $this->logPath;
        }
        $install->execute() !== false or die('Install failed.');
    }
}


