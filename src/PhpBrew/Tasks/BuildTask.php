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

    public function build($build,$options)
    {
        $this->info("===> Building...");
        $cmd = new CommandBuilder('make');
        $cmd->append = true;
        if ($this->logPath) {
            $cmd->stdout = $this->logPath;
        }

        if ( $options->nice ) {
            $cmd->nice($options->nice);
        }

        $this->debug( $cmd->__toString()  );

        if ( ! $options->dryrun ) {
            $startTime = microtime(true);
            $cmd->execute() !== false or die('Make failed.');
            $buildTime = (int)((microtime(true) - $startTime) / 60);
            $this->info("Build finished: $buildTime minutes.");
        }
    }
}


