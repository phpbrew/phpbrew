<?php

namespace PhpBrew\Tasks;

use PhpBrew\Exception\SystemCommandException;
use PhpBrew\CommandBuilder;
use PhpBrew\Build;

/**
 * Task to run `make install`.
 */
class InstallTask extends BaseTask
{
    public function install(Build $build)
    {
        $this->info('Installing...');

        if ($this->options->sudo) {
            $cmd = new CommandBuilder('sudo make install');
            if (!$this->options->dryrun) {
                $code = $cmd->passthru($lastline);
                if ($code !== 0) {
                    throw new SystemCommandException("Install failed: $lastline", $build, $build->getBuildLogPath());
                }
            }
        } else {
            $cmd = new CommandBuilder('make install');
            $cmd->setAppendLog(true);
            $cmd->setLogPath($build->getBuildLogPath());
            $cmd->setStdout($this->options->{'stdout'});
            if (!$this->options->dryrun) {
                $code = $cmd->execute($lastline);
                if ($code !== 0) {
                    throw new SystemCommandException("Install failed: $lastline", $build, $build->getBuildLogPath());
                }
            }
        }
        $build->setState(Build::STATE_INSTALL);
    }
}
