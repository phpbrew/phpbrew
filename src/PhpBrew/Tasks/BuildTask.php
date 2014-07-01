<?php
namespace PhpBrew\Tasks;
use PhpBrew\CommandBuilder;

/**
 * Task to run `make`
 */
class BuildTask extends BaseTask
{
    public function setLogPath($path)
    {
        $this->logPath = $path;
    }

    public function build($build, $options)
    {
        $this->info("===> Building...");
        $cmd = new CommandBuilder('make');
        $cmd->append = true;

        if ($this->logPath) {
            $cmd->stdout = $this->logPath;
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
            $cmd->execute() !== false or die('Make failed.');
            $buildTime = ceil((microtime(true) - $startTime) / 60);
            $this->info("Build finished: $buildTime minutes.");
        }
    }
}
