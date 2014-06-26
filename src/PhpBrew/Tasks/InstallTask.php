<?php
namespace PhpBrew\Tasks;
use PhpBrew\CommandBuilder;

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

    public function install($build, $options)
    {
        $this->info("Installing...");
        $cmd = new CommandBuilder('make install');
        $cmd->append = true;

        if ($this->logPath) {
            $cmd->stdout = $this->logPath;
        }

        if (! $options->dryrun) {
            $cmd->execute() !== false or die('Install failed.');
        }
    }
}
