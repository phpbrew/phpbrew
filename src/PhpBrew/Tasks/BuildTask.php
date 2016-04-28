<?php
namespace PhpBrew\Tasks;
use PhpBrew\Exception\SystemCommandException;
use PhpBrew\CommandBuilder;
use PhpBrew\Build;

/**
 * Task to run `make`
 */
class BuildTask extends BaseTask
{
    public function run(Build $build, $targets = array())
    {
        if ($build->getState() >= Build::STATE_BUILD) {
        $this->info("===> Already built, skipping...");

            return;
        }

        $this->info("===> Building...");
        $cmd = new CommandBuilder('make');

        $cmd->setAppendLog(true);
        $cmd->setLogPath($build->getBuildLogPath());
        $cmd->setStdout($this->options->{'stdout'});

        if (!empty($targets)) {
            foreach ($targets as $t) {
                $cmd->addArg($t);
            }
        }

        if ($this->options->nice) {
            $cmd->nice($this->options->nice);
        }

        if ($makeJobs = $this->options->{'jobs'}) {
            $cmd->addArg("-j{$makeJobs}");
        }

        $this->debug($cmd->getCommand());

        if (!$this->options->dryrun) {
            $startTime = microtime(true);
            $code = $cmd->execute($lastline);
            if ($code !== 0) {
                throw new SystemCommandException("Make failed: $lastline", $build, $build->getBuildLogPath());
            }
            $buildTime = round((microtime(true) - $startTime) / 60, 1);
            $this->info("Build finished: $buildTime minutes.");
        }
        $build->setState(Build::STATE_BUILD);
    }
}
