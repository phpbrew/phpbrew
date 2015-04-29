<?php
namespace PhpBrew\Tasks;

use RuntimeException;
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
        $cmd->setStdout($this->options->{'stdout'});
        if (!$this->options->dryrun) {
            $code = $cmd->execute();
            if ($code != 0) {
                throw new RuntimeException('Install failed.', 1);
            }

        }
        $build->setState(Build::STATE_INSTALL);
    }
}
