<?php
namespace PhpBrew\Tasks;
use PhpBrew\CommandBuilder;
use PhpBrew\Build;

/**
 * Task to run `make`
 */
class BuildTask extends BaseTask
{
    public function run(Build $build, $targets = array())
    {
        $this->info("===> Building...");
        $cmd = new CommandBuilder('make');

        $cmd->setAppendLog(true);
        $cmd->setLogPath($build->getBuildLogPath());

        if (!empty($targets)) {
            foreach($targets as $t) {
                $cmd->addArg($t);
            }
        }

        if ($this->options->nice) {
            $cmd->nice($this->options->nice);
        }

        if ($makeJobs = $this->options->{'make-jobs'}) {
            $cmd->addArg("-j{$makeJobs}");
        }

        $this->debug($cmd->getCommand());

        if (!$this->options->dryrun) {
            $startTime = microtime(true);
            $code = $cmd->execute();
            if ($code != 0 )
                die('Make failed.');
            $buildTime = round((microtime(true) - $startTime) / 60, 1);
            $this->info("Build finished: $buildTime minutes.");
        }
    }
}
