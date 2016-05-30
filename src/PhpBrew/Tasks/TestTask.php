<?php

namespace PhpBrew\Tasks;

use PhpBrew\Exception\SystemCommandException;
use PhpBrew\Build;
use PhpBrew\CommandBuilder;

/**
 * Task to run `make test`.
 */
class TestTask extends BaseTask
{
    public function run(Build $build, $nice = null)
    {
        $this->info('===> Running tests...');
        $cmd = new CommandBuilder('make test');

        if ($nice) {
            $cmd->nice($nice);
        }

        $cmd->setAppendLog(true);
        $cmd->setLogPath($build->getBuildLogPath());
        $cmd->setStdout($this->options->{'stdout'});

        putenv('NO_INTERACTION=1');
        $this->debug(''.$cmd);
        $code = $cmd->execute($lastline);
        if ($code !== 0) {
            throw new SystemCommandException("Test failed: $lastline", $build);
        }
    }
}
