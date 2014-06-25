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

    public function build($nice = null, $makeJobs = null)
    {
        $this->info("===> Building...");
        $cmd = new CommandBuilder('make');
        $cmd->append = true;
        if ($this->logPath) {
            $cmd->stdout = $this->logPath;
        }
        if ($nice) {
            $cmd->nice($nice);
        }
        if ($makeJobs > 1) {
            $cmd->addArg("-j{$makeJobs}");
        }
        $this->debug( '' .  $cmd  );
        $startTime = microtime(true);
        $cmd->execute() !== false or die('Make failed.');
        $buildTime = ceil((microtime(true) - $startTime) / 60);
        $this->info("Build finished: $buildTime minutes.");
    }
}
