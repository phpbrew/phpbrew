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

    public function build(Build $build, $options)
    {
        $this->info("===> Building...");
        $cmd = new CommandBuilder('make');
        $cmd->append = true;

        if ($this->logPath) {
            $cmd->stdout = $this->logPath;
        } else {
            $cmd->stdout = $build->getBuildLogPath();
        }

        if ($options->nice) {
            $cmd->nice($options->nice);
        }

        if ($makeJobs = $options->{'make-jobs'}) {
            $cmd->addArg("-j{$makeJobs}");
        }

        $this->debug($cmd->__toString());

        if (!$options->dryrun) {
            $startTime = microtime(true);
            $code = $cmd->execute();
            if ($code != 0 )
                die('Make failed.');
            $buildTime = ceil((microtime(true) - $startTime) / 60);
            $this->info("Build finished: $buildTime minutes.");
        }
    }
}
