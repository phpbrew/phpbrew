<?php
namespace PhpBrew\Tasks;
use PhpBrew\CommandBuilder;
use PhpBrew\Build;

/**
 * Task to run `make`
 */
class BuildTask extends BaseTask
{
    public function setLogPath($path)
    {
        $this->logPath = $path;
    }

    public function build(Build $build)
    {
        $this->info("===> Building...");
        $cmd = new CommandBuilder('make');
        $cmd->append = true;

        if ($this->logPath) {
            $cmd->stdout = $this->logPath;
        } else {
            $cmd->stdout = $build->getBuildLogPath();
        }

        if ($this->options->nice) {
            $cmd->nice($this->options->nice);
        }

        if ($makeJobs = $this->options->{'make-jobs'}) {
            $cmd->addArg("-j{$makeJobs}");
        }

        $this->debug($cmd->__toString());

        if (!$this->options->dryrun) {
            $startTime = microtime(true);
            $code = $cmd->execute();
            if ($code != 0 )
                die('Make failed.');
            $buildTime = ceil((microtime(true) - $startTime) / 60);
            $this->info("Build finished: $buildTime minutes.");
        }
    }
}
