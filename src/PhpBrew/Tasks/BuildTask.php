<?php
namespace PhpBrew\Tasks;
use PhpBrew\CommandBuilder;
use PhpBrew\Config;

/**
 * Task to run `make`
 */
class BuildTask extends BaseTask 
{
    public function setLogPath($path)
    {
        $this->logPath = $path;
    }

    public function build($nice = null)
    {
        $this->info("===> Building...");
        $cmd = new CommandBuilder('make');
        $cmd->append = true;
        if($this->logPath) {
            $cmd->stdout = $this->logPath;
        }
        if( $nice ) {
            $cmd->nice($nice);
        }
        $this->debug( '' .  $cmd  );
        $startTime = microtime(true);
        $cmd->execute() !== false or die('Make failed.');
        $buildTime = (int)((microtime(true) - $startTime) / 60);
        $this->info("Build finished: $buildTime minutes.");
    }
}


