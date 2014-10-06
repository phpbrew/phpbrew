<?php
namespace PhpBrew\Tasks;

use PhpBrew\CommandBuilder;
use PhpBrew\Build;

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

    public function install(Build $build, $options)
    {
        $this->info("Installing...");
        $cmd = new CommandBuilder('make install');

        /*
         * XXX: stderr redirection will make the execute return code = 0
        $cmd->append = true;
        if ($this->logPath) {
            $cmd->stdout = $this->logPath;
        }
        */

        if (!$options->dryrun) {
            $code = $cmd->execute();
            if ($code != 0)
                die('Install failed.');
        }
    }
}
