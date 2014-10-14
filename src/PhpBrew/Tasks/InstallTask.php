<?php
namespace PhpBrew\Tasks;

use PhpBrew\CommandBuilder;
use PhpBrew\Build;

/**
 * Task to run `make install`
 */
class InstallTask extends BaseTask
{
    public function install(Build $build)
    {
        $this->info("Installing...");
        $cmd = new CommandBuilder('make install');
        $cmd->setAppendLog(true);
        $cmd->setLogPath($build->getBuildLogPath());
        if (!$this->options->dryrun) {
            $code = $cmd->execute();
            if ($code != 0)
                die('Install failed.');
        }
    }
}
